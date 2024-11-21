export function parseArgs(defaultArgs, args) {
	return {
		...defaultArgs,
		...args,
	};
}
