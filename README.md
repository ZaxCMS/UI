This library contains some useful UI extensions for Nette framework.

Zax\Application\UI\IAjaxAware
=============================

Defines one method `enableAjax()` and is - quite obviously - intended to enable AJAX features.

Zax\Application\UI\Control
==========================

This class extends standard Nette Control and adds some interesting features, including `IAjaxAware` implementation
and views. Its main purpose is to save us from typing repetitive boiler-plate code when doing some common
tasks and makes AJAXification of a finished component much easier, with consistent results.

Example
-------

A very simple component might look like this:

```php
class SomeControl extends Zax\Application\UI\Control {

	public function beforeRender() {
		// gets called before a component gets rendered
	}

	public function viewDefault() {
		// gets called only if view is "Default"
	}

}
```

A component also needs a template. So let's add a 'Default.latte' into 'templates' directory, relative
to where the component is located. The structure will look like this:

- SomeControl.php
- /templates
	- Default.latte

That's it!

Using views
-----------

Views are defined by view\<View\> methods. If you try to access an undefined view, an
exception will be thrown. Also each view has a corresponding template with same name.

Views are internally nothing but a persistent param `$view`, so creating links to views couldn't be any easier:

```php
$this->link('this', ['view' => 'Foo']);
```

Using renders
-------------

In Nette, we sometimes might want to use something like this: `{control someControl:foo}`. Normally, we'd make a
`renderFoo` method, but this component is based on `__call` magic method, so this wouldn't work properly. Instead,
let's call our method `beforeRenderFoo`. And again, it needs a separate template, which would be (assuming we are still in
"Default" view) 'Default.Foo.latte'.

*The pattern for naming templates is '\<View\>.latte' or '\<View\>.\<Render\>.latte'.*

We can pass parameters to renders as well, so `{control someControl:foo, bar => val}` will call`beforeRenderFoo`
method and pass "val" to parameter called "bar".

Working with AJAX
-----------------

I'm not gonna beat around the bush. AJAX is magic and it's made to suit my needs, so you might find some WTF factors
here. But since I took the time to implement it, I might as well document it too.

First thing we need to do is call `enableAjax` on our freshly created component (in `createComponent*` method) and
wrap our component in a `{snippet}` (without name) in our component's template. From now on, when we send an AJAX request,
the component should automatically redraw it's snippet. Note that this will enable AJAX on
all subcomponents as well (well, on all subcomponents, that implement `IAjaxAware`).

**Known limitation:** Nette creates components on-demand. That means, if a component doesn't receive any parameters
during a request, it will get created after it is demanded in a template, which is too late for AJAX redrawing. A
simple workaround is to create the component manually in action, like this:

```php
public function actionDefault() {
	$this->createComponent('someControl');
	// or shorter
	$this['someControl'];
}
```

Okay, now we have a component that knows about AJAX and knows whether AJAX is enabled or not. Now is time to create
some links that respect this settings. There are two ways to do it, one which respects default nette.ajax.js settings,
but requires a little bit more boiler-plate code, and another, which uses `n:ajax` macro.

To use the first method, just check `$control->isAjaxEnabled()` (or `$control->ajaxEnabled` thanks to Nette\Object
magic) before adding class "ajax" to your link, like this:

```
<a n:href="this, view => Foo" n:class="$control->ajaxEnabled ? ajax">link</a>
```

The other method looks a little bit more elegant in templates:

```
<a n:href="this, view => Foo" n:ajax>link</a>
```

This n:macro does the very same check on your component, but instead of adding a class, it adds `data-zax-ajax`
attribute. To make it work, we need to add this piece of code to our js before calling `$.nette.init()`:

```js
$.nette.ext('init').linkSelector = 'a[data-zax-ajax]';
```

Simple, right? Now, there's one last thing to cover. If you ever tried to AJAXify your app, you've probably went
down the path where you kept writing `if is ajax, redraw, else redirect` like a mofo all the time and usually you
were able to only use AJAX in signals, because you cannot call `redrawControl` when eg. setting a persistent param
or something like that.

I've been down that path as well and it plain sucked. We already have automatic snippet invalidation, which
solves the persistent params part, but what about the `if is ajax blahblahblah`? Well, I've added a method called `go`,
which does this check for us AND ensures we end up on the same destination, no matter whether it's AJAX request or
not.

So calling `$this->go('signal!', ['view' => 'Foo']);` will check whether it's AJAX request or not and will either
perform regular redirect to signal and Foo view, or it will just forward us to the same destination, without making
an additional request. It couldn't be any easier!

Zax\Application\UI\Multiplier
=============================

Multiplier is a cool little class that allows us to have multiple instances of the same component on one page.
Default Nette Multiplier will however prevent sub-components from receiving the ajaxEnabled state, because it's not
`IAjaxAware`. So I made it `IAjaxAware`.

Customization
=============

Control behavior is divided into several traits:

TControlForward
---------------

`forward()` and `presenterForward()` methods.

TControlAjax
------------

`IAjaxAware` implementation + `TControlForward`

TControlLifeCycle
-----------------

Life cycle using `__call`, calls `view*()` and `beforeRender*()` and adds a persistent parameter `$view`.
Also, if a control implements `IHasControlLifeCycle`, then it automatically calls `run()` method, which we can
use to render a template or do whatever we want.

TControlMergeLinkParams
-----------------------

Allows us to specify `$defaultLinkParams` in specific components to keep URLs as clean as possible when using
multiple (sub)components with persistent params.
