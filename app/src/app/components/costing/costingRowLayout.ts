/** Shared grid for costing product rows — metrics columns align across all rows. */
export const costingRowGridClass =
  "grid w-full items-center gap-x-2 sm:gap-x-3 " +
  "[grid-template-columns:minmax(0,1fr)_auto_1.25rem] " +
  "sm:[grid-template-columns:minmax(6rem,1fr)_4.25rem_4.25rem_minmax(4.5rem,5.25rem)_4rem_1.25rem]";

/** Honey rows include a color bar column. */
export const costingHoneyRowGridClass =
  "grid w-full items-center gap-x-2 sm:gap-x-3 " +
  "[grid-template-columns:0.25rem_minmax(0,1fr)_auto_1.25rem] " +
  "sm:[grid-template-columns:0.25rem_minmax(6rem,1fr)_4.25rem_4.25rem_minmax(4.5rem,5.25rem)_4rem_1.25rem]";

export const costingRowHeaderClass =
  "text-[10px] font-semibold uppercase tracking-wide text-muted-foreground";
