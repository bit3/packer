<?php

/*
 * CSS/JS distribution packer.
 *
 * (c) Tristan Lins <tristan.lins@bit3.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bit3\Builder\Meta;

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

class Package
{
	/**
	 * The package name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The package target pathname.
	 *
	 * @var string
	 */
	protected $pathname;

	/**
	 * The extending package name.
	 *
	 * @var string
	 */
	protected $extends = null;

	/**
	 * Filters used by this package.
	 *
	 * @var FilterInterface[]
	 */
	protected $filters = [];

	/**
	 * Files in this package.
	 *
	 * @var File[]
	 */
	protected $files = [];

	/**
	 * Files that should be watched.
	 *
	 * @var File[]
	 */
	protected $watches = [];

	/**
	 * Flag that this package is virtual or not.
	 *
	 * @var bool
	 */
	protected $virtual = false;

	function __construct($name, $pathname)
	{
		$this->name     = (string) $name;
		$this->pathname = (string) $pathname;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $pathname
	 */
	public function setPathname($pathname)
	{
		$this->pathname = (string) $pathname;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPathname()
	{
		return $this->pathname;
	}

	/**
	 * @param string $extends
	 */
	public function setExtends($extends)
	{
		$this->extends = empty($extends) ? null : (string) $extends;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getExtends()
	{
		return $this->extends;
	}

	/**
	 * @param \Assetic\Filter\FilterInterface[] $filters
	 */
	public function addFilter(FilterInterface $filter, $name)
	{
		$this->filters[$name] = $filter;
		return $this;
	}

	/**
	 * @param \Assetic\Filter\FilterInterface[] $filters
	 */
	public function setFilters(array $filters)
	{
		$this->filters = $filters;
		return $this;
	}

	/**
	 * @return \Assetic\Filter\FilterInterface[]
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * @param File $files
	 */
	public function addFile(File $file)
	{
		$this->files[] = $file;
		return $this;
	}

	/**
	 * @param File[] $files
	 */
	public function setFiles(array $files)
	{
		$this->files = $files;
		return $this;
	}

	/**
	 * @return File[]
	 */
	public function getFiles()
	{
		return $this->files;
	}

	/**
	 * @param File $watch
	 */
	public function addWatch(File $watch)
	{
		$this->watches[] = $watch;
		return $this;
	}

	/**
	 * @param File[] $watches
	 */
	public function setWatches($watches)
	{
		$this->watches = $watches;
		return $this;
	}

	/**
	 * @return File[]
	 */
	public function getWatches()
	{
		return $this->watches;
	}

	/**
	 * @param boolean $virtual
	 */
	public function setVirtual($virtual)
	{
		$this->virtual = (bool) $virtual;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isVirtual()
	{
		return $this->virtual;
	}

	function __clone()
	{
		$this->files = array_map(
			function ($file) {
				return clone $file;
			},
			$this->files
		);
	}
}
