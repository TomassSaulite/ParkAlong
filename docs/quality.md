# Quality checklist

Use this checklist before a public demo or release.

## Accessibility

- [ ] Every form control has a visible label or an equivalent accessible name.
- [ ] Keyboard users can reach, operate, and dismiss every interactive control.
- [ ] Focus states remain visible in light and dark themes.
- [ ] Dialogs, menus, timers, and navigation expose the correct semantic state.
- [ ] Text and controls meet WCAG AA contrast targets.
- [ ] The main flow works at mobile widths without horizontal scrolling.

## Performance

- [ ] Run a Lighthouse audit against the deployed demo.
- [ ] Check the landing route and the primary authenticated flow separately.
- [ ] Investigate large JavaScript bundles and unoptimized images.
- [ ] Confirm the app remains usable on a slow mobile connection.
- [ ] Record the audit date and the main regression risk in the release notes.

## Release hygiene

- [ ] CI is green on the release commit.
- [ ] Environment variables are documented without real secrets.
- [ ] Known limitations are written down.
- [ ] A short manual smoke test has been completed on desktop and mobile.
- [ ] The changelog describes user-visible changes.
