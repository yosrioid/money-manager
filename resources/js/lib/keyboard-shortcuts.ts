/**
 * Whether a keydown event represents the "submit the current form"
 * shortcut (Ctrl+Enter on Windows/Linux, Cmd+Enter on macOS).
 *
 * Useful for forms containing multi-line textareas, where a plain
 * `Enter` keypress inserts a newline instead of submitting.
 */
export function isSubmitShortcut(event: KeyboardEvent): boolean {
    return (event.ctrlKey || event.metaKey) && event.key === 'Enter';
}
