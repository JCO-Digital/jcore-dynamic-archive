import { createSlotFill } from '@wordpress/components';

export const DYNAMIC_ARCHIVE_INSPECTOR_SLOTS = Object.freeze({
	general: 'jcore/dynamic-archive/inspector-general',
	layout: 'jcore/dynamic-archive/inspector-layout',
	filters: 'jcore/dynamic-archive/inspector-filters',
});

const { Fill: DynamicArchiveInspectorGeneralFill, Slot: DynamicArchiveInspectorGeneralRawSlot } =
	createSlotFill(DYNAMIC_ARCHIVE_INSPECTOR_SLOTS.general);
const { Fill: DynamicArchiveInspectorLayoutFill, Slot: DynamicArchiveInspectorLayoutRawSlot } =
	createSlotFill(DYNAMIC_ARCHIVE_INSPECTOR_SLOTS.layout);
const { Fill: DynamicArchiveInspectorFiltersFill, Slot: DynamicArchiveInspectorFiltersRawSlot } =
	createSlotFill(DYNAMIC_ARCHIVE_INSPECTOR_SLOTS.filters);

function DynamicArchiveInspectorGeneralSlot({ extensionContext }) {
	return <DynamicArchiveInspectorGeneralRawSlot fillProps={extensionContext} />;
}

function DynamicArchiveInspectorLayoutSlot({ extensionContext }) {
	return <DynamicArchiveInspectorLayoutRawSlot fillProps={extensionContext} />;
}

function DynamicArchiveInspectorFiltersSlot({ extensionContext }) {
	return <DynamicArchiveInspectorFiltersRawSlot fillProps={extensionContext} />;
}

export {
	DynamicArchiveInspectorGeneralFill,
	DynamicArchiveInspectorLayoutFill,
	DynamicArchiveInspectorFiltersFill,
	DynamicArchiveInspectorGeneralSlot,
	DynamicArchiveInspectorLayoutSlot,
	DynamicArchiveInspectorFiltersSlot,
};
