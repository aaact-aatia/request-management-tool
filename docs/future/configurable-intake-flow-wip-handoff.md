# Configurable intake flow: work-in-progress handoff

Management decided the first release should use a much simpler request-intake process. The configurable intake-flow work is being deferred to a later version rather than discarded.

## ChatGPT link to discussion
https://chatgpt.com/g/g-p-6830917751148191ae63054f2fe867c7/c/6a5a22a6-cd30-83ea-b37e-aabe7d86e2e2

## Original plan

Build a configurable, bilingual intake system that:

- Uses database-defined questions, options, and branching paths.
- Starts after the user selects a catalogue and service.
- Supports JavaScript and no-JavaScript operation.
- Saves progress using an intake run token.
- Supports optional or required free-text answers.
- Validates answers on both the client and server.
- Prevents stale or duplicate updates using revision numbers.
- Preserves accessibility features such as error summaries, focus management, labels, and retained form values.

## Work completed

The current work includes:

- A new intake-flow controller and shared PHP helper.
- Integration with the three existing request pages and Ajax endpoints.
- A client-side intake controller in `openrequest.js`.
- English and French interface strings.
- Database seed changes for the configurable flow.
- Documentation describing the configurable intake architecture.
- Server-rendered validation and no-JavaScript form processing.
- A fix that focuses the validation summary after a failed no-JavaScript submission.
- Preservation of the run token, selected option, entered text, and links to invalid fields.

## Validation completed

The following checks passed:

- PHP syntax checks for all changed PHP files.
- JavaScript syntax checking.
- Git whitespace checking.
- Disposable database seed testing.
- Browser acceptance scenarios.
- Focused no-JavaScript validation testing.
- Database cleanup after testing.
- Confirmation that the disposable response tables remained empty.

## Known blockers

This work is not production-ready.

### Session concurrency

Intake session mutations are not genuinely atomic. Concurrent requests could accept the same revision and overwrite one another because the current database session handler does not lock the complete read-modify-write operation.

A repeatable parallel-request regression test is still required.

### Initial no-JavaScript path

The existing no-JavaScript browser test created the intake run with JavaScript enabled before disabling JavaScript.

The initial catalogue and service selection on `openrequest.php` still depends on JavaScript. A complete server-rendered path and a browser test that disables JavaScript before the first page load are still required.

## Resuming the work

When this work is restarted:

1. Add atomic or serialized intake-session mutations.
2. Add a repeatable concurrent-request regression test.
3. Make catalogue and service selection work from the initial page without JavaScript.
4. Add a browser test that disables JavaScript before initially loading `openrequest.php`.
5. Rerun the complete validation suite.
6. Review the complete diff before considering the feature production-ready.
