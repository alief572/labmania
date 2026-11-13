# Third-party material

This repository's own work is under the PolyForm Noncommercial License 1.0.0 (`LICENSE`).
Third-party files keep their own licences, listed here. A skill added under
`.claude/skills/` should be credited here, or be written for this framework.

## Vendored

| What | Upstream | Licence | Notes |
|---|---|---|---|
| `drawio` skill (`.claude/skills/drawio/`) | Adapted from the draw.io Claude Code plugin in [jgraph/drawio-mcp](https://github.com/jgraph/drawio-mcp) | Apache-2.0 per the upstream repository page (`.claude/skills/drawio/LICENSE` is the licence text) | Adapted for this framework; the credit line is also at the end of its `SKILL.md` |
| `antislop` skills (`.claude/skills/antislop/`, `antislop-ui/`, `antislop-human/`, `antislop-layoutmobile/`, `antislop-copywriting/`, `antislop-code/`) | [miqdadbadjuber/anti-slop](https://github.com/miqdadbadjuber/anti-slop) v3.2.25, commit `5d90a9a` (05-11-2026), copied unchanged | MIT (`LICENSE` in each folder); `references/` adapted by upstream from Addy Osmani's web-quality-skills, MIT (`LICENSE-web-quality-skills`) | Used for every mockup change, mode *during* (`CLAUDE.md` → Mockups use antislop). Update: re-copy `skills/*` from upstream |

Written for this framework: `harvest`, and everything under `context/`, `docs/`, `pm/`
and `input/`.

## Not vendored

- **`xlsx`** (Anthropic). Source-available, not open source: its licence forbids keeping
  copies outside Anthropic's Services, copying, derivative works and redistribution, so it
  is not in this repository (and `.claude/skills/xlsx/` is gitignored). The workbook
  conventions work with `openpyxl` alone; Claude Code can use the `xlsx` skill from
  Anthropic's skills plugin if you have it.
- **`doc-coauthoring`** (anthropics/skills), removed 2026-10-09. Its upstream folder holds
  only `SKILL.md` and the repository README gives no per-skill licence, so reuse terms
  could not be confirmed.

## Recommended skills

Not part of this repository: install them yourself and check each licence first.

| Skill | What it does | Licence |
|---|---|---|
| [caveman](https://github.com/JuliusBrussee/caveman) | Terse reply mode: answer first, no fluff, technical facts kept; cuts output tokens | Apache-2.0 |
| [ponytail](https://github.com/DietrichGebert/ponytail) | "Lazy senior dev" mode: the smallest change that fully solves the task, plus review and audit skills | MIT |
