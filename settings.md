## Basic Settings

The arguments `id`, `name`, `desc` and `type` are __required__ for every field! Arguements in __bold__ are required.

#### header (implements a simple header)

Beyond the required options, this field type takes no arguments.

#### checkbox (implements a checkbox)

|Field|Description|Default|
|-----|-----------|-------|
|__options__|A multi-dimensional array of options in the format `'option-value' => 'Option Name'`|`null`|

#### color (implements a color select field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field as an RGB hex code|empty string|

#### descriptive_text (implements a descriptive text field)

Beyond the required options, this field type takes no arguments.

#### editor (implements a rich text editor)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|
|allow_blank|A boolean specifying whether or not to allow blank values. If set to false and a blank value is saved, will revert to the value of `std`|`true`|
|size|The number of rows to display for this field as an integer|`10`|
|wpautop|A boolean specifying whether or not to run text through the wpautop filter|`true`|
|buttons|A boolean specifying whether or not to display the editor buttons|`true`|
|teeny|A boolean specifying whether or not to use the editor 'teeny' mode|`false`|

#### html (implements a CodeMirror HTML field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|

#### multicheck (implements a multicheck field)

|Field|Description|Default|
|-----|-----------|-------|
|__options__|A multi-dimensional array of options in the format `'option-value' => 'Option Name'`|`null`|

#### number (implements a number field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|
|max|An integer identifying the highest allowed value for this field|`999999`|
|min|An integer identifying the lowest allowed value for this field|`0`|
|step|A float identifying what to increment the field by when the up/down arrows are pressed|`1`|
|size|The size to display for this field. Valid values are `small`, `regular` and `large`|`regular`|

#### password (implements a password field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|
|size|The size to display for this field. Valid values are `small`, `regular` and `large`|`regular`|

#### radio (implements a radio button list field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default item key for this field|empty string|

#### select (implements a select field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default item key for this field|empty string|
|placeholder|The placeholder to display if no option is selected|empty string|
|select2|A boolean specifying whether or not to implement the Select2 library for this field|`false`|
|multiple|A boolean specifying whether the select box should allow multiple selections|`false`|

#### text (implements a text field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|
|readonly|A boolean specifying whether or not this field should be read only|`false`|
|size|The size to display for this field. Valid values are `small`, `regular` and `large`|`regular`|

#### textarea (implements a text area)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|

#### upload (implements an upload field)

|Field|Description|Default|
|-----|-----------|-------|
|std|The default value for this field|empty string|

#### hook (implements a custom field through a WordPress action)

Beyond the required options, this field type takes no arguments. This field is rendered based on the action `your_plugin_<field_id>`. In other words, if you added a hook field with an ID of `custom_field`, you would have to hook `your_plugin_custom_field` to render it.

## Settings methods

The following methods are available for working with your settings:

#### get_option( $key, $default );

|Arguement|Description|
|---------|-----------|
|$key|`string` The settings key to retrieve|
|$default|`mixed` A default value to return if the key is unavailable|

#### update_option( $key, $value );

|Arguement|Description|
|---------|-----------|
|$key|`string` The settings key to update|
|$value|`mixed` The new value for this key. __If you leave this empty or set it to `false`, the key will be deleted!__|

#### delete_option( $key );

|Arguement|Description|
|---------|-----------|
|$key|`string` The settings key to delete|

#### get_settings();

Retrieves all settings, does not take an arguement.

## Comments

Beyond the above listed methods, there are numerous hooks scattered throughout the library. Please feel free to tear it appart, figure out how it works, and improve it if you can!
