---
name: rtl-bilingual-ui
description: Rules for any Blade/Livewire/Tailwind UI work so screens are fully Arabic/English and RTL/LTR. Use whenever creating or editing views, components, emails, exports or UI strings.
---
# RTL and bilingual UI

Also follow the tailwindcss-development and laravel-best-practices skills; this skill only adds the bilingual rules.

- No hardcoded UI text. All strings go in lang files (lang/ar and lang/en), with identical keys in both. Use translation keys for validation messages, enum labels, table headers, buttons, empty states, emails and export headings.
- Per-user locale: the layout sets lang and dir (rtl for ar, ltr for en) on the html element from the authenticated user's locale setting, with a sensible fallback.
- Use logical Tailwind utilities only: ms-/me-/ps-/pe-/start-/end-/text-start/text-end/border-s/border-e/rounded-s/rounded-e. Avoid ml-/mr-/pl-/pr-/left-/right-/text-left/text-right unless a rtl:/ltr: variant is added on purpose. Icons that imply direction (arrows, chevrons) must flip in RTL.
- Fonts must support both Arabic and Latin; set them as design tokens, not per component.
- Numbers and dates: display with the locale in mind (Arabic-Indic or Western digits per user setting), and never parse user input without normalizing Arabic-Indic digits first. Use Carbon/Intl for dates, not manual formatting.
- Mixed-script text (Arabic names inside English UI and vice versa): wrap user-provided names and identifiers with bdi or dir="auto" so they do not scramble punctuation or order.
- Tables and exports: column order and alignment follow the direction; Excel/PDF exports support both languages.
- Figma designs: implement faithfully; if a design only exists for one direction, mirror it logically and tell the owner what you assumed.
- Every new screen needs a feature test that renders it in both ar and en and asserts that dir is correct and no raw translation keys appear.
