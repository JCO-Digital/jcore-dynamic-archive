/**
 * Hooks
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { applyFilters } from '@wordpress/hooks';
import usePostTypes from '@/shared/usePostTypes';

/**
 * Components
 */
import {
	Button,
	SearchControl,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import { arrowUp, arrowDown, closeSmall } from '@wordpress/icons';

/**
 * Dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { speak } from '@wordpress/a11y';
import { decodeEntities } from '@wordpress/html-entities';

const SEARCH_DELAY = 300;
const MIN_SEARCH_LENGTH = 2;

/**
 * Searches posts through the REST search endpoint.
 *
 * @param {Object}      args   Query args for /wp/v2/search.
 * @param {AbortSignal} signal Signal to cancel the request.
 * @return {Promise<Array<{id: number, title: string, subtype: string}>>} Found posts.
 */
function fetchPosts(args, signal) {
	return apiFetch({
		path: addQueryArgs('/wp/v2/search', {
			type: 'post',
			_fields: 'id,title,subtype',
			...args,
		}),
		signal,
	}).then((posts) =>
		posts.map((post) => ({
			id: post.id,
			title: decodeEntities(post.title ?? ''),
			subtype: post.subtype,
		}))
	);
}

/**
 * PostPicker
 *
 * Lets the editor search for posts, add them to a list, and reorder or remove
 * them. Focus stays on the control that was used, or moves to its nearest
 * neighbour when that control goes away, and every change is announced.
 *
 * @hooked jcore.postPicker.postTypes - Filters the post types that can be searched.
 *
 * @param {Object}                   props
 * @param {number[]}                 props.value    Selected post IDs, in display order.
 * @param {(value: number[]) => void} props.onChange Called with the new list of IDs.
 * @return {Element} Element to render.
 */
export default function PostPicker({ value, onChange }) {
	const instanceId = useInstanceId(PostPicker, 'jcore-post-picker');
	const [search, setSearch] = useState('');
	const [results, setResults] = useState([]);
	const [status, setStatus] = useState('idle');
	// Post ID -> { title, subtype }, or null when the post can't be shown.
	const [posts, setPosts] = useState({});
	const [focusTarget, setFocusTarget] = useState(null);
	const searchRef = useRef();
	const buttonRefs = useRef(new Map());

	const { postTypes, loading: postTypesLoading } = usePostTypes();
	const searchablePostTypes = applyFilters(
		'jcore.postPicker.postTypes',
		postTypes.map((postType) => postType.slug)
	);
	const subtype =
		searchablePostTypes.length > 0 ? searchablePostTypes.join(',') : 'any';

	const remember = (found) =>
		setPosts((previous) => {
			const next = { ...previous };
			for (const post of found) {
				next[post.id] = post;
			}
			return next;
		});

	// Search as the editor types.
	useEffect(() => {
		const query = search.trim();
		if (query.length < MIN_SEARCH_LENGTH || postTypesLoading) {
			setResults([]);
			setStatus('idle');
			return;
		}
		const controller = new AbortController();
		setStatus('loading');
		const timer = setTimeout(() => {
			fetchPosts(
				{ search: query, subtype, per_page: 20 },
				controller.signal
			)
				.then((found) => {
					remember(found);
					setResults(found);
					setStatus('done');
					speak(
						found.length === 0
							? __('No posts found.', 'jcore-dynamic-archive')
							: sprintf(
									// translators: %d: Number of posts found.
									_n(
										'%d post found.',
										'%d posts found.',
										found.length,
										'jcore-dynamic-archive'
									),
									found.length
								)
					);
				})
				.catch((error) => {
					if (error?.name === 'AbortError') {
						return;
					}
					setResults([]);
					setStatus('error');
				});
		}, SEARCH_DELAY);
		return () => {
			clearTimeout(timer);
			controller.abort();
		};
	}, [search, subtype, postTypesLoading]);

	// Look up the titles of posts that were picked earlier.
	useEffect(() => {
		const missing = value.filter((id) => !(id in posts));
		if (missing.length === 0) {
			return;
		}
		const controller = new AbortController();
		fetchPosts(
			{ include: missing.join(','), subtype: 'any', per_page: 100 },
			controller.signal
		)
			.then((found) => {
				remember(found);
				// Whatever the search did not return is unpublished or gone.
				setPosts((previous) => {
					const next = { ...previous };
					for (const id of missing) {
						if (!(id in next)) {
							next[id] = null;
						}
					}
					return next;
				});
			})
			.catch(() => {});
		return () => controller.abort();
	}, [value]);

	useEffect(() => {
		if (focusTarget === null) {
			return;
		}
		const element =
			focusTarget === 'search'
				? searchRef.current
				: buttonRefs.current.get(focusTarget);
		element?.focus();
		setFocusTarget(null);
	}, [focusTarget, value]);

	const setButtonRef = (key) => (element) => {
		if (element) {
			buttonRefs.current.set(key, element);
		} else {
			buttonRefs.current.delete(key);
		}
	};

	const getTitle = (id) => {
		const post = posts[id];
		if (post === null) {
			// translators: %d: Post ID.
			return sprintf(__('Post %d', 'jcore-dynamic-archive'), id);
		}
		if (!post) {
			return __('Loading…', 'jcore-dynamic-archive');
		}
		return post.title || __('(no title)', 'jcore-dynamic-archive');
	};

	const getPostTypeName = (slug) =>
		postTypes.find((postType) => postType.slug === slug)?.labels
			?.singular_name ?? slug;

	const addPost = (post) => {
		if (value.includes(post.id)) {
			return;
		}
		const next = [...value, post.id];
		onChange(next);
		speak(
			sprintf(
				// translators: 1: Post title. 2: Number of selected posts.
				_n(
					'%1$s added. %2$d post selected.',
					'%1$s added. %2$d posts selected.',
					next.length,
					'jcore-dynamic-archive'
				),
				getTitle(post.id),
				next.length
			)
		);
	};

	const movePost = (index, offset) => {
		const id = value[index];
		const newIndex = index + offset;
		if (newIndex < 0 || newIndex >= value.length) {
			return;
		}
		const next = [...value];
		next.splice(index, 1);
		next.splice(newIndex, 0, id);
		onChange(next);
		setFocusTarget(`${id}:${offset < 0 ? 'up' : 'down'}`);
		speak(
			sprintf(
				// translators: 1: Post title. 2: New position. 3: Number of selected posts.
				__(
					'%1$s moved to position %2$d of %3$d.',
					'jcore-dynamic-archive'
				),
				getTitle(id),
				newIndex + 1,
				next.length
			)
		);
	};

	const removePost = (index) => {
		const id = value[index];
		const next = value.toSpliced(index, 1);
		onChange(next);
		const neighbour = next[index] ?? next[index - 1];
		setFocusTarget(neighbour ? `${neighbour}:remove` : 'search');
		speak(
			sprintf(
				// translators: %s: Post title.
				__('%s removed.', 'jcore-dynamic-archive'),
				getTitle(id)
			)
		);
	};

	const selectedHeadingId = `${instanceId}-selected`;

	return (
		<div className="jcore-post-picker">
			<SearchControl
				ref={searchRef}
				label={__('Search for posts to add', 'jcore-dynamic-archive')}
				hideLabelFromVision={false}
				placeholder=""
				value={search}
				onChange={setSearch}
				help={__(
					'Type at least two letters.',
					'jcore-dynamic-archive'
				)}
				__nextHasNoMarginBottom
			/>
			<div className="jcore-post-picker__results">
				{status === 'loading' && <Spinner />}
				{status === 'error' && (
					<Text>
						{__(
							'The search failed. Please try again.',
							'jcore-dynamic-archive'
						)}
					</Text>
				)}
				{status === 'done' && results.length === 0 && (
					<Text>{__('No posts found.', 'jcore-dynamic-archive')}</Text>
				)}
				{status === 'done' && results.length > 0 && (
					<ul
						className="jcore-post-picker__list"
						aria-label={__('Search results', 'jcore-dynamic-archive')}
					>
						{results.map((post) => {
							const added = value.includes(post.id);
							const title = getTitle(post.id);
							return (
								<li
									key={post.id}
									className="jcore-post-picker__item"
								>
									<span className="jcore-post-picker__title">
										{title}
										<span className="jcore-post-picker__meta">
											{getPostTypeName(post.subtype)}
										</span>
									</span>
									<Button
										variant="secondary"
										size="small"
										disabled={added}
										accessibleWhenDisabled
										aria-label={
											added
												? sprintf(
														// translators: %s: Post title.
														__(
															'Added: %s',
															'jcore-dynamic-archive'
														),
														title
													)
												: sprintf(
														// translators: %s: Post title.
														__(
															'Add %s',
															'jcore-dynamic-archive'
														),
														title
													)
										}
										onClick={() => addPost(post)}
									>
										{added
											? __('Added', 'jcore-dynamic-archive')
											: __('Add', 'jcore-dynamic-archive')}
									</Button>
								</li>
							);
						})}
					</ul>
				)}
			</div>
			<h3
				id={selectedHeadingId}
				className="jcore-post-picker__heading"
			>
				{sprintf(
					// translators: %d: Number of selected posts.
					__('Selected posts (%d)', 'jcore-dynamic-archive'),
					value.length
				)}
			</h3>
			{value.length === 0 ? (
				<Text>
					{__(
						'No posts selected yet. Search for posts above and add them.',
						'jcore-dynamic-archive'
					)}
				</Text>
			) : (
				<ol
					className="jcore-post-picker__list jcore-post-picker__list--selected"
					aria-labelledby={selectedHeadingId}
				>
					{value.map((id, index) => {
						const title = getTitle(id);
						return (
							<li key={id}>
								<div className="jcore-post-picker__item">
									<span className="jcore-post-picker__title">
										{title}
										<span className="jcore-post-picker__meta">
											{posts[id] === null
												? __(
														'Not published or not found. It will not be shown.',
														'jcore-dynamic-archive'
													)
												: getPostTypeName(
														posts[id]?.subtype
													)}
										</span>
									</span>
									<Button
										ref={setButtonRef(`${id}:up`)}
										icon={arrowUp}
										size="small"
										label={sprintf(
											// translators: %s: Post title.
											__(
												'Move %s up',
												'jcore-dynamic-archive'
											),
											title
										)}
										disabled={index === 0}
										accessibleWhenDisabled
										onClick={() => movePost(index, -1)}
									/>
									<Button
										ref={setButtonRef(`${id}:down`)}
										icon={arrowDown}
										size="small"
										label={sprintf(
											// translators: %s: Post title.
											__(
												'Move %s down',
												'jcore-dynamic-archive'
											),
											title
										)}
										disabled={index === value.length - 1}
										accessibleWhenDisabled
										onClick={() => movePost(index, 1)}
									/>
									<Button
										ref={setButtonRef(`${id}:remove`)}
										icon={closeSmall}
										size="small"
										isDestructive
										label={sprintf(
											// translators: %s: Post title.
											__(
												'Remove %s',
												'jcore-dynamic-archive'
											),
											title
										)}
										onClick={() => removePost(index)}
									/>
								</div>
							</li>
						);
					})}
				</ol>
			)}
		</div>
	);
}
