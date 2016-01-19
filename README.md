# S214 Settings API Library

## What Is This?

Depending on the size of a project, you may be able to get away with adding settings to an existing WordPress page (or the customizer). On the other end of the spectrum, you may need (or want) to implement a full-scale control panel like [Redux](http://reduxframework.com). But what if your project is somewhere in the middle? Or what if you need a control panel, but don't want the bulk that goes along with most? This is an issue that I've struggled with for some time. My standard implementation has historically been a fork of the awesome system used by [Easy Digital Downloads](https://github.com/easydigitaldownloads/Easy-Digital-Downloads). However, this has its own set of issues. For each project, I had to sort through the various required files and update function and variable names to prevent conflicts, and the process of implementing it was arduous. Thus, I finally sat down and converted it into a reusable library which I am now sharing with the general public! Read on to check out the basic implementation.

## Basic Setup

We're going to assume that the slug for your project is `your-plugin`. Instantiating the settings library would go something like this:

```<?php
global $your_plugin_options;

if( ! class_exists( 'S214_Settings' ) ) {
  require_once __DIR__ . 'source/class.s214-settings.php';
}

$settings = new S214_Settings( 'your-plugin', 'general' );
$your_plugin_options = $settings->get_settings();
```

The S214_Settings class takes two arguments. The first, is the slug of your project. The second is the slug of the tab we want to load by default on the settings page. More on settings tabs in a moment.

At this point, we now have a global `$your_plugin_options` containing an array of the available options for your plugin. Of course, we haven't defined and options, so right now it's just a blank array... not very useful!

Adding a settings panel through this library consists of three basic components: Adding the settings themselves, adding settings tabs (grouping settings), and adding a menu item for the settings page. Let's start with adding the settings menu.

### Adding A Menu Item

Adding a menu item looks like this (remember, your plugin slug is `your-plugin`):

```
function your_plugin_add_menu( $menu ) {
  $menu['page_title'] = __( 'Your Plugin Settings', 'your-plugin' );
  $menu['menu_title'] = __( 'Your Plugin', 'your-plugin' );

  return $menu;
}
add_filter( 'your_plugin_menu', 'your_plugin_add_menu' );
```

This is about as simple a configuration as you can get for the menu (technically, you can actually leave those out and use the defaults, but that would just look silly). However, there are actually several other defaults you can override to configure your menu as you see fit...

```
function your_plugin_add_menu( $menu ) {
  $menu['type']       = 'submenu';                                    // Can be set to 'submenu' or 'menu'. Defaults to 'menu'.
  $menu['parent']     = 'options-general.php';                        // If 'type' is set to 'submenu', defines the parent menu to place our menu under. Defaults to 'options-general.php'
  $menu['page_title'] = __( 'Your Plugin Settings', 'your-plugin' );  // The page title. Defaults to 'Section214 Settings'.
  $menu['menu_title'] = __( 'Your Plugin', 'your-plugin' );           // The menu title. Defaults to 'Section214 Settings'.
  $menu['capability'] = 'manage_options';                             // The minimum capability required to access the settings panel. Defaults to 'manage_options'.
  $menu['position']   = null;                                         // Where in the menu to display our new menu. Defaults to 'null' (bottom of the menu).

  return $menu;
}
add_filter( 'your_plugin_menu', 'your_plugin_add_menu' );
```

That's it! Now that we've added our menu, maybe we should provide some content. Let's start with some tabs:

### Adding Settings Tabs

Even if you don't need more than one tab, registering one tab is required (we recommend just calling it 'general'). Here's how:

```
function your_plugin_settings_tabs( $tabs ) {
  $tabs['general'] = __( 'General', 'your-plugin' );

  return $tabs;
}
add_filter( 'your_plugin_settings_tabs', 'your_plugin_settings_tabs' );
```

Again, pretty simple. You can add as many tabs as you want through this filter, though add too many and it might start to look a bit odd!

Starting with version 1.0.1, we now support settings sections. This allows you to create sub-tabs in each main tab. This can be accomplished like so...

```
function your_plugin_settings_sections( $sections ) {
	$sections = array(
		'general' => array(
			'main' => __( 'General Settings', 'your-plugin' )
		)
	);

	return $sections;
}
add_filter( 'your_plugin_settings_sections', 'your_plugin_settings_sections' );
```

Sections are completely optional, but they do make it easier to sort though a lot of settings!

Finally, let's populate that tab with some settings:

### Adding Settings

There are a _lot_ of possible options in creating your settings, so we're going to keep this super simple and let your read in more detail in the [settings reference](https://github.com/Section214/S214-Settings/blob/master/settings.md).

```
function your_plugin_settings( $settings ) {
  $plugin_settings = array(
    'general' => array(
      'main' => array(
        array(
          'id'   => 'your_first_setting',
          'name' => __( 'Your First Setting', 'your-plugin' ),
          'desc' => __( 'This is your first setting!', 'your-plugin' ),
          'type' => 'text'
        )
      )
    )
  );

  return array_merge( $settings, $plugin_settings );
}
add_filter( 'your_plugin_registered_settings', 'your_plugin_settings' );
```

With this simple configuration, you should now have a settings page that looks like this:

![](http://cloud.section214.com/image/0y3K0C3H0b0V/Image%202015-10-20%20at%201.38.16%20AM.png)

Breaking down the settings array, you can see that it is a multi-dimensional array where each settings tab is a multi-dimensional array, and each setting is an array.

## Accessing Settings

So you've added a few settings, now what?

For the moment, we're going to assume that when you instantiated the settings class, you stored it to a variable we can access as `$settings`. To retrieve a specific setting, you'd simply do this:

`$settings->get_option( 'your_first_setting' );`

The `get_option()` method takes two arguments: `key` and `default`. The first arguement, key, is mandatory. This is the ID of the setting we want to retrieve. The second arguement is optional and specifies a default value to return if the specified key isn't saved.

That's it! You're all set! Don't forget to read the [settings reference](https://github.com/Section214/S214-Settings/blob/master/settings.md) for further info storing and working with settings!
