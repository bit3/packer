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

Deployment
----------

Using the "watch" feature is nice for local development, but what if you must compile local and push to a remote host?
This may necessary when your website is placed on a shared host, without shell/compiler support.

Using deployment is realy easy, first you need to define your deployment commands:

```yaml
deploy:
	- notify-send "Build %file% finished"
```

And use the `--deploy` option to your command: `pack --deploy`

After each package, the deployment will be executed. The placeholder `%file%` is replaced with the pathname of the
package, `%package%` with the name of the package and `%deploy%` with the deployment target (which is `default` by
default).

**Output example**

```
$ pack dist/package.css --deploy
parse configuration
+ load .../bit3/packer/src/default.yml
+ load package.yml
+ load package.local.yml

build package dist/package.css
* build collection from dist/package.css
  ~ filters:
    - cssrewrite [Assetic\Filter\CssRewriteFilter]
  + add local file src/reset.css
* write file dist/package.css

deploy to notify
  * exec notify-send "Build dist/package.css finished"
```

### Deployment targets

Having one deployment target is nice for simple setups, but what if you need to deploy to multiple places depending on
what you currently work on? One possible usage scenario is a preview and production deployment.

When you add the `deploy` section, all commands will be added to the `default` deployment targets. To define multiple
deployment targets, group the commands inside of the `deploy` section.

```yaml
deploy:
	preview:
		- scp %file% user@preview.example.com:/var/www/vhosts/preview.example.com/assets/
	production:
		- scp %file% user@example.com:/var/www/vhosts/example.com/assets/
```

Now you have two deployment targets which can be used particular by `pack --deploy preview` and
`pack --deploy production`, or together with a single `pack --deploy preview --deploy production`.

**Hint** There is simply no difference between

```yaml
deploy:
	- <cmd>
```

and

```yaml
deploy:
	default:
		- <cmd>
```

**Hint** It is also possible to mix grouped and non-grouped commands:

```yaml
deploy:
	- <cmd 1>
	production:
		- <cmd 2>
```

which is exactly the same as:

```yaml
deploy:
	default:
		- <cmd 1>
	production:
		- <cmd 2>
```

**Warning** If you want to deploy specific packages (`pack dist/only_this_package.css`) and deploy these to the
`default` deployment target (`pack --deploy`), the package name may be assumed as deployment target, if you just use
`pack --deploy dist/only_this_package.css`. Just add `default` after `--deploy`:
`pack --deploy default dist/only_this_package.css` or simply switch the parameters:
`pack dist/only_this_package.css --deploy`.

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
