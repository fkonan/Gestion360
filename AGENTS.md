# Project Agent Instructions

## Project Type
- Laravel 11 monolithic application
- Blade templates with Blade Components (x-*)
- Bootstrap 5.3 for UI

## Backend Rules
- Use Eloquent as the default ORM
- Raw SQL exists in some places and MUST NOT be modified unless explicitly requested
- Do not invent models, tables, columns, routes, or services
- Controllers should remain thin when possible

## Frontend Rules
- Keep Bootstrap 5.3 conventions
- Do not introduce other CSS frameworks
- Respect existing Blade layouts and components

## Agent Behavior
- Always analyze files before modifying them
- Explicitly state which files will be modified and why
- Apply changes only after user confirmation
- Prefer safe changes over large refactors

## Constraints
- No breaking changes unless explicitly requested
- Do not refactor the entire project

## UI / UX Rules
- When redesigning or improving UI, prioritize aesthetic, clean, and modern layouts using Bootstrap 5.3.
- Always maintain visual and structural consistency with the rest of the project (spacing, colors, typography, components).
- Do not introduce new visual styles, color palettes, or layout patterns that conflict with existing views.
- Prefer improving existing Bootstrap-based structures over creating entirely new designs.



