<?php

/*
 * CSS/JS distribution packer.
 *
 * (c) Tristan Lins <tristan.lins@bit3.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bit3\Builder;

use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\FilterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Assetic\Filter\CssEmbedFilter;
use Assetic\Filter\Sass\ScssFilter;
use Assetic\Filter\Yui\CssCompressorFilter;
use Assetic\Filter\Yui\JsCompressorFilter;
use Assetic\Filter\CssCrushFilter;
use Symfony\Component\Yaml\Yaml;

class StringFile implements File
{
	/**
	 * The content.
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * File specific filters.
	 *
	 * @var FilterInterface[]
	 */
	protected $filters = [];

	function __construct($content, array $filters = [])
	{
		$this->content = (string) $content;
		$this->filters  = $filters;
	}

	public function setContent($pathname)
	{
		$this->content = (string) $pathname;
		return $this;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function addFilter(FilterInterface $filter, $name)
	{
		$this->filters[$name] = $filter;
		return $this;
	}

	public function setFilters(array $filters)
	{
		$this->filters = $filters;
		return $this;
	}

	public function getFilters()
	{
		return $this->filters;
	}
}
