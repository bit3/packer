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

use Assetic\Filter\FilterInterface;

interface File
{
	/**
	 * @param \Assetic\Filter\FilterInterface[] $filters
	 * @param string                            $name
	 */
	public function addFilter(FilterInterface $filter, $name);

	/**
	 * @return \Assetic\Filter\FilterInterface[]
	 */
	public function getFilters();

	/**
	 * @param \Assetic\Filter\FilterInterface[] $filters
	 */
	public function setFilters(array $filters);
}