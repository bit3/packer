CSS/JS distribution packer
==========================

Add a `package.yml` to your repository and run the `pack` command.

Configuration
-------------

There are 3 configuration files.

The [`default.yml`](src/default.yml) contains the default configuration.

The `package.yml` contains the package configuration, for your project. This one should be added to your repository.

The `package.local.yml` contains system / environment specific configuration (like paths to binaries). This one should
not be added to your repository.

They will be loaded in this order and merged into each other. That means in general you `package.yml` contains the
package information, while the `package.local.yml` contains paths to the binaries on your system.

**Convenience:** If you want to provide a `package.local.yml` that works for your team, for example all use the same
system environment, put a `package.local.yml.dist` with system environment specific settings into you repository. You
should not overcharge the `package.yml` with system environment specific settings!

Watching modifications
----------------------

A lot compilers like sass/compass have a "watch" feature. But when using chains of compilers and minifiers, this is not
easy to automating. Use the `--watch` (`-w`) flag will cause the packer to watch for modifications on the packages.

If you using importing (like the sass `@import` rule) or embedding (like the cssembed filter), you may want to specify
which files must be watched?! Simply add a `watch` block into your `package.yml`, directly after the `files` block and
specify the files or directories to watch.

```yaml
packages:
	dist/package.css:
		files:
			# remind that all files here will be watched anyway
			- reset.css
		watch:
			# watch a single file
			- src/file_to_watch.css
			# directories are also allowed
			- assets/images/
			# or watch all watches from another package
			- @dist/other_package.css
```



`package.yml` reference
-----------------------

```yaml
packages:
	dist/package.css:
		filters: [cssrewrite]
		files:
			# add a string
			- |
				/*
				 * (c) <copyright holders>
				 */

			# add a static file
			- src/reset.css

			# add a file with filters
			- [src/base.scss, [scss]]
		watch:
			# watch a file
			- src/file.css

			# watch a directory
			- assets/images/

			# watch files from another package
			- @dist/other_package.css

	dist/package.min.css:
		# extend another package
		extends: dist/package.css

		# remind that this will overwrite the filters, not extend
		filters: [cssrewrite, yui-css]

	vendors:
		# this is a virtual package, that will not be build
		virtual: true
		files:
			- framework.css

	dist/package.with-vendors.css:
		# this will merge two packages into one
		files:
			# an @<name> will referencing another package,
			# so you can merge and mixin multiple packages
			- @dist/package.css
			- @vendors
```
