# App-wide feedback motion

## Pattern

Single kit for exec, inventory, deliveries, and other non-POS pages. CSR fulfillment re-exports hover tokens and `AppPressable` as `FulfillmentPressable`.

## File

`app/src/app/components/layout/appFeedbackMotion.tsx`

## When editing UI

| Situation | Use |
|-----------|-----|
| Save / task done | `AppSuccessNotice` or `AppTaskCompletePanel` |
| API / validation error | `AppErrorBanner` |
| Header Guide button | `appHeaderActionClass` |
| Modal footer CTA | `AppPressable` |
| Form submit | shadcn `Button` (global tap/hover) |
| Modal shell | `AppModal` (already animated) |

## Agent rule

`.cursor/rules/feedback-animations.mdc` — `alwaysApply: true`

## Related

- [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]]
- `onlineFulfillmentMotion.tsx` — CSR aliases
- `posMotion.tsx` — POS only
