CSS/JS distribution packer
==========================

Add a `dist.yml` to your repository and run the `pack` command.

Configuration
-------------

There are 3 configuration files.

The [`default.yml`](src/default.yml) contains the default configuration.

The `package.yml` contains the package configuration, for your project.

The `package.local.yml` contains system / environment specific configuration (like paths to binaries).

They will be loaded in this order and merged into each other. That means in general you `package.yml` contains the
package information, while the `package.local.yml` contains paths to the binaries on your system.

Example `package.yml`
---------------------

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
