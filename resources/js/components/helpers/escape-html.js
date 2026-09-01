const REPLACEMENTS = {
	"&": "&amp;",
	"<": "&lt;",
	">": "&gt;",
	'"': "&quot;",
	"'": "&#39;",
};

/**
 * Escape a value for interpolation into an HTML template string.
 *
 * Escapes the element content characters (& < >) and the quote characters
 * (" '), so the same helper is safe both between tags and inside a quoted
 * attribute value.
 */
export default function escapeHtml(value) {
	return String(value ?? "").replace(
		/[&<>"']/g,
		(character) => REPLACEMENTS[character],
	);
}
