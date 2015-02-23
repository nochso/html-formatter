<?php

namespace Mihaeu;

/**
 * HTML Formatter
 *
 * @author Michael Haeuslmann <haeuslmann@gmail.com>
 */
class HtmlFormatter
{
	/**
	 * Formats HTML by re-indenting the tags and removing unnecessary whitespace.
	 *
	 * @param string $html HTML string.
	 * @param string $indentWith Characters that are being used for indentation (default = 4 spaces).
	 * @param string $tagsWithoutIndentation Comma-separated list of HTML tags that should not be indented (default = html,link,img,meta)
	 *
	 * @return string Re-indented HTML.
	 */
	public static function format($html, $indentWith = '    ', $tagsWithoutIndentation = 'html,link,img,meta')
	{
		// remove all line feeds and replace tabs with spaces
		$html = str_replace(["\n", "\r", "\t"], ['', '', ' '], $html);
		$elements = preg_split('/(<.+>)/U', $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$dom = self::parseDom($elements);

		$indent = 0;
		$output = [];
		$tagsWithoutIndentationHash = array_flip(explode(',', $tagsWithoutIndentation));
		foreach ($dom as $index => $element) {
			if ($element['opening']) {
				$output[] = "\n" . str_repeat($indentWith, $indent) . trim($element['content']);

				// make sure that only the elements who have not been blacklisted are being indented
				if (!isset($tagsWithoutIndentationHash[$element['type']])) {
					++$indent;
				}
			} else if ($element['standalone']) {
				$output[] = "\n" . str_repeat($indentWith, $indent) . trim($element['content']);
			} else if ($element['closing']) {
				--$indent;
				$lf = "\n" . str_repeat($indentWith, $indent);
				if (isset($dom[$index - 1]) && $dom[$index - 1]['opening']) {
					$lf = '';
				}
				$output[] = $lf . trim($element['content']);
			} else if ($element['text']) {
				$output[] = "\n" . str_repeat($indentWith, $indent) . preg_replace('/ [ \t]*/', ' ', $element['content']);
			} else if ($element['comment']) {
				$output[] = "\n" . str_repeat($indentWith, $indent) . trim($element['content']);
			}
		}

		return trim(implode('', $output));
	}

	/**
	 * Parses an array of HTML tokens and adds basic information about about the type of
	 * tag the token represents.
	 *
	 * @param Array $elements Array of HTML tokens (tags and text tokens).
	 *
	 * @return Array HTML elements with extra information.
	 */
	public static function parseDom(Array $elements)
	{
		$dom = [];
		foreach ($elements as $element) {
			$isText = false;
			$isComment = false;
			$isClosing = false;
			$isOpening = false;
			$isStandalone = false;

			$currentElement = trim($element);

			if (strpos($currentElement, '<!') === 0) {
				$isComment = true;
			} else if (strpos($currentElement, '</') === 0) {
				$isClosing = true;
			} else if (preg_match('/\/>$/', $currentElement)) {
				$isStandalone = true;
			} else if (strpos($currentElement, '<') === 0) {
				$isOpening = true;
			} else {
				$isText = true;
			}

			$dom[] = [
				'text'       => $isText,
				'comment'    => $isComment,
				'closing'    => $isClosing,
				'opening'    => $isOpening,
				'standalone' => $isStandalone,
				'content'    => $element,
				'type'       => preg_replace('/^<\/?(\w+)[ >].*$/U', '$1', $element)
			];
		}
		return $dom;
	}
}