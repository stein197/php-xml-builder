<?php
	namespace STEIN197\XMLBuilder;

	// TODO: parse DOMDocument::loadHTML?
	// TODO: attributes on new line?
	// TODO: Add CDATA?
	// TODO: Add comments support?
	/**
	 * The main class of the package.
	 */
	class Builder {

		/** @var int Consider output as XML markup. Empty tags end with forward slash. */
		public const MODE_XML = 1;
		/** @var int Consider output as XML markup. Empty tags may end with forward slash. */
		public const MODE_HTML = 2;
		/** @var int Outputs minified markup. */
		public const OUTPUT_MINIFIED = 1;
		/** @var int Outputs beautified markup. */
		public const OUTPUT_BEAUTIFIED = 2;

		/** @var array Stores all data. */
		private $data = [];

		/**
		 * @param array $xmlAttributes If present, adds <?xml?> element
		 *                             with presented attributes.
		 */
		public function __construct(array $xmlAttributes = []) {
			if ($xmlAttributes) {
				$attributes = [];
				foreach ($xmlAttributes as $k => $v)
					$attributes[] = "{$k}=\"{$v}\"";
				$attributes = join(' ', $attributes);
				$this->data[] = "<?xml {$attributes}?>";
			}
		}

		/**
		 * Returns minified markup.
		 * @return string
		 */
		public function __toString(): string {
			return $this->stringify(self::OUTPUT_MINIFIED, self::MODE_HTML);
		}

		/**
		 * Each nonexistent method converts to XML tag.
		 * @return self Builder to chain tag methods.
		 */
		public function __call(string $method, array $arguments): self {
			$tagName = Tag::createTagNameFromMethodName($method);
			$content = $attributes = [];
			foreach ($arguments as $arg) {
				if (is_array($arg)) {
					$attributes = $arg;
				} elseif (is_callable($arg)) {
					$builder = new self;
					$result = $arg($builder);
					if ($result === null)
						$content = array_merge($content, $builder->data);
					else
						$content[] = $result;
				} elseif ($arg instanceof self) {
					$content = array_merge($content, $arg->data);
				} else {
					$content[] = $arg;
				}
			}
			$tag = new Tag($tagName, $content, $attributes);
			$this->data[] = $tag;
			return $this;
		}

		/**
		 * Stringifies the builder's data.
		 * @param int $stringify One of the OUTPUT_* constants.
		 * @param int $stringify One of the MODE_* constants.
		 * @return string String representation of the inner structure.
		 */
		public function stringify(int $stringify, int $mode): string {
			$result = '';
			foreach ($this->data as $content) {
				if ($content instanceof Tag)
					$result .= $content->stringify($stringify, $mode);
				else
					$result .= $content;
				if ($stringify === self::OUTPUT_BEAUTIFIED)
					$result .= "\n";
			}
			return $result;
		}
	}
