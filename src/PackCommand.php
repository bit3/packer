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

use Assetic\Asset\HttpAsset;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\Sass\SassFilter;
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

class PackCommand extends \Symfony\Component\Console\Command\Command
{
	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * @var FilterInterface[]
	 */
	protected $filters = [];

	/**
	 * @var Package[]
	 */
	protected $packages = [];

	protected function configure()
	{
		$this->setName('pack');
		$this->addOption(
			'config',
			'c',
			InputOption::VALUE_REQUIRED,
			'Path to configuration file.',
			'package.yml'
		);
		$this->addOption(
			'local',
			'l',
			InputOption::VALUE_REQUIRED,
			'Path to local configuration file, that contain environment specific settings.',
			'package.local.yml'
		);
		$this->addArgument(
			'package',
			InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
			'Build specific packages only.'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->parseConfig($input, $output);

		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('');
		}

		$this->buildPackages($input, $output);
	}

	/**
	 * Parse the package.yml config file.
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 */
	protected function parseConfig(InputInterface $input, OutputInterface $output)
	{
		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('parse configuration');
		}

		$defaultsFile = __DIR__ . DIRECTORY_SEPARATOR . 'default.yml';
		$configFile   = $input->getOption('config');
		$localFile    = $input->getOption('local');

		if (!file_exists($configFile)) {
			throw new \RuntimeException('The config file "' . $configFile . '" does not exist');
		}

		$yaml = new Yaml();

		// load default configuration
		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('+ load ' . $defaultsFile);
		}

		$config = $yaml->parse($defaultsFile);

		// load package configuration
		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('+ load ' . $configFile);
		}

		$config = $this->mergeConfig($config, $yaml->parse($configFile));

		// if exists, load local configuration
		if (file_exists($localFile)) {
			if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
				$output->writeln('+ load ' . $localFile);
			}

			$config = $this->mergeConfig($config, $yaml->parse($localFile));
		}

		if (isset($config['config']) && is_array($config['config'])) {
			$this->config += $config['config'];
		}

		if (isset($config['filter']) && is_array($config['filter'])) {
			foreach ($config['filter'] as $filterName => $filterConfig) {
				$this->parseFilter($filterName, $filterConfig);
			}
		}

		if (isset($config['packages']) && is_array($config['packages'])) {
			foreach ($config['packages'] as $packageName => $packageConfig) {
				$this->parsePackage($packageName, $packageConfig);
			}
		}
	}

	/**
	 * Recursive merge configuration, overwriting leafs instead of merging them.
	 *
	 * @param array $config
	 * @param array $extend
	 *
	 * @return array
	 */
	protected function mergeConfig(array $config, array $extend)
	{
		foreach ($extend as $key => $value) {
			// if value is an array
			if (is_array($value)) {
				// ... merge with existing array
				if (isset($config[$key]) && is_array($config[$key])) {
					$config[$key] = $this->mergeConfig($config[$key], $value);
				}
				// ... or overwrite if the existing value is not an array
				else {
					$config[$key] = $value;
				}
			}
			// if value is scalar
			else {
				// overwrite the existing value
				$config[$key] = $value;
			}
		}

		return $config;
	}

	/**
	 * Parse a single filter configuration.
	 *
	 * @param string $name
	 * @param array  $config
	 *
	 * @return FilterInterface
	 */
	protected function parseFilter($name, $config)
	{
		$className = $config['class'];
		$class     = new \ReflectionClass($className);

		if (isset($config['arguments'])) {
			$arguments = $this->replacePlaceholders($config['arguments']);
		}
		else {
			$arguments = [];
		}

		$filter = $class->newInstanceArgs($arguments);

		foreach ($config as $property => $value) {
			if ($property == 'class' || $property == 'arguments') {
				continue;
			}

			$setterName = 'set' . ucfirst($property);
			$setter     = $class->getMethod($setterName);
			$setter->invoke($filter, $value);
		}

		$this->filters[$name] = $filter;

		return $filter;
	}

	/**
	 * Parse a single package configuration.
	 *
	 * @param string $name
	 * @param array  $config
	 *
	 * @return Package
	 */
	protected function parsePackage($name, $config)
	{
		$package = new Package($name, $name);

		if (isset($config['pathname'])) {
			$package->setPathname($this->replacePlaceholders($config['pathname']));
		}

		if (isset($config['extends'])) {
			$package->setExtends($this->replacePlaceholders($config['extends']));
		}

		if (isset($config['filters'])) {
			foreach ($this->replacePlaceholders($config['filters']) as $filterName) {
				$package->addFilter($this->filters[$filterName], $filterName);
			}
		}

		if (isset($config['files'])) {
			foreach ($this->replacePlaceholders($config['files']) as $fileConfig) {
				$fileConfig = (array) $fileConfig;

				$file = $fileConfig[0];
				if ($file[0] == '@') {
					$file =  new PackageFile(substr($file, 1));
				}
				else if (preg_match('~^\w+:~', $file)) {
					$file = new RemoteFile($file);
				}
				else if (file_exists($file)) {
					$file = new LocalFile($file);
				}
				else {
					$file = new StringFile($file);
				}

				if (isset($fileConfig[1])) {
					$filterNames = (array) $fileConfig[1];
					foreach ($filterNames as $filterName) {
						$file->addFilter($this->filters[$filterName], $filterName);
					}
				}

				$package->addFile($file);
			}
		}

		$this->packages[$name] = $package;

		return $package;
	}

	/**
	 * Replace placeholders in the input.
	 *
	 * @param array|string $input
	 *
	 * @return mixed
	 */
	protected function replacePlaceholders($input)
	{
		// replace placeholders in array values
		if (is_array($input)) {
			foreach ($input as $key => $value) {
				$input[$key] = $this->replacePlaceholders($value);
			}
		}

		// replace single placeholders, this may also return a non-string value
		else if (preg_match('~^%([^%]+)%$~', $input, $matches)) {
			$input = $this->replacePlaceholder($matches[1]);
		}

		// replace multiple placeholders in a string
		else {
			$input = preg_replace_callback(
				'~%([^%]+)%~',
				function ($matches) {
					return $this->replacePlaceholder($matches[1]);
				},
				$input
			);

			if (empty($input)) {
				$input = null;
			}
		}

		return $input;
	}

	/**
	 * Resolve a single placeholder key and return the replacement value.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function replacePlaceholder($key)
	{
		if ($key == 'cwd') {
			return getcwd();
		}
		else if ($key == 'lib') {
			return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
		}
		else if (isset($this->config[$key])) {
			return $this->replacePlaceholders($this->config[$key]);
		}
		else if (isset($this->filters[$key])) {
			return $this->filters[$key];
		}
		return null;
	}

	protected function buildPackages(InputInterface $input, OutputInterface $output)
	{
		$packageNames = $input->getArgument('package');

		if (empty($packageNames)) {
			$packageNames = array_keys($this->packages);
		}

		foreach ($packageNames as $packageName) {
			if (!isset($this->packages[$packageName])) {
				throw new \RuntimeException('The package "' . $packageName . '" does not exist');
			}

			$package = $this->packages[$packageName];

			if ($package->isVirtual()) {
				continue;
			}

			$this->buildPackage($package, $output);
		}
	}

	protected function buildPackage(Package $package, OutputInterface $output)
	{
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln(sprintf('build package <comment>%s</comment>', $package->getPathname()));
		}

		if (count($package->getFilters()) && $output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
			$output->writeln('  ~ filters:');

			foreach ($package->getFilters() as $filterName => $filter) {
				$output->writeln(sprintf('    - <comment>%s</comment> [%s]', $filterName, get_class($filter)));
			}
		}

		$asset = $this->buildAssetCollection($package, $output);
		$asset->setTargetPath($package->getPathname());
		$buffer = $asset->dump();

		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln(sprintf('* write file <comment>%s</comment>', $package->getPathname()));
		}

		file_put_contents($package->getPathname(), $buffer);

		if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
			$output->writeln('');
		}
	}

	/**
	 * Build the assets list for a package.
	 *
	 * @param Package         $package
	 * @param OutputInterface $output
	 *
	 * @return AssetCollection
	 */
	protected function buildAssetCollection(Package $package, OutputInterface $output)
	{
		if ($package->getExtends()) {
			if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
				$output->writeln(sprintf('* extend package <comment>%s</comment>', $package->getExtends()));
			}

			$extendPackage = $this->packages[$package->getExtends()];
			$assets        = $this->buildAssetCollection($extendPackage, $output);

			if (count($package->getFilters())) {
				$assets->clearFilters();

				foreach ($package->getFilters() as $filter) {
					$assets->ensureFilter($filter);
				}
			}
		}
		else {
			$assets = new AssetCollection([], $package->getFilters(), getcwd());
		}

		if ($package->getFiles()) {
			foreach ($package->getFiles() as $file) {
				if ($file instanceof PackageFile) {
					$mergePackage = $this->packages[$file->getPackageName()];
					$asset = $this->buildAssetCollection($mergePackage, $output);

					foreach ($file->getFilters() as $filter) {
						$asset->ensureFilter($filter);
					}
				}
				else if ($file instanceof Remotefile) {
					if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
						$output->writeln(sprintf('+ add remote file <comment>%s</comment>', $file->getUrl()));
					}

					$asset = new HttpAsset($file->getUrl(), $file->getFilters());
				}
				else if ($file instanceof StringFile) {
					if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
						$output->writeln('+ add <comment>string</comment>');
					}

					$asset = new StringAsset($file->getContent(), $file->getFilters());
				}
				else if ($file instanceof LocalFile) {
					if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
						$output->writeln(sprintf('+ add local file <comment>%s</comment>', $file->getPathname()));
					}

					$asset = new FileAsset($file->getPathname(), $file->getFilters());
				}
				else {
					continue;
				}

				if (count($file->getFilters()) && $output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
					$output->writeln('  ~ filters:');

					foreach ($file->getFilters() as $filterName => $filter) {
						$output->writeln(sprintf('    - <comment>%s</comment> [%s]', $filterName, get_class($filter)));
					}
				}

				$assets->add($asset);
			}
		}

		return $assets;
	}
}
