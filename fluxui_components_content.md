# FluxUI Components Documentation

Generated on: 2025-06-04 13:02:49

## Accordion

Collapse and expand sections of content. Perfect for FAQs and content-heavy areas.

```html
<flux:accordion>
    <flux:accordion.item>
        <flux:accordion.heading>What's your refund policy?</flux:accordion.heading>

        <flux:accordion.content>
            If you are not satisfied with your purchase, we offer a 30-day money-back guarantee. Please contact our support team for assistance.
        </flux:accordion.content>
    </flux:accordion.item>

    <flux:accordion.item>
        <flux:accordion.heading>Do you offer any discounts for bulk purchases?</flux:accordion.heading>

        <flux:accordion.content>
            Yes, we offer special discounts for bulk orders. Please reach out to our sales team with your requirements.
        </flux:accordion.content>
    </flux:accordion.item>

    <flux:accordion.item>
        <flux:accordion.heading>How do I track my order?</flux:accordion.heading>

        <flux:accordion.content>
            Once your order is shipped, you will receive an email with a tracking number. Use this number to track your order on our website.
        </flux:accordion.content>
    </flux:accordion.item>
</flux:accordion>
```

### Shorthand
You can save on markup by passing the heading text as a prop directly.

```html
<flux:accordion.item heading="What's your refund policy?">
    If you are not satisfied with your purchase, we offer a 30-day money-back guarantee. Please contact our support team for assistance.
</flux:accordion.item>
```

### With transition
Enable expanding transitions for smoother interactions.

```html
<flux:accordion transition>
    <!-- ... -->
</flux:accordion>
```

### Disabled
Restrict an accordion item from being expanded.

```html
<flux:accordion.item disabled>
    <!-- ... -->
</flux:accordion.item>
```

### Exclusive
Enforce that only a single accordion item is expanded at a time.

```html
<flux:accordion exclusive>
    <!-- ... -->
</flux:accordion>
```

### Expanded
Expand a specific accordion by default.

```html
<flux:accordion.item expanded>
    <!-- ... -->
</flux:accordion.item>
```

### Leading icon
Display the icon before the heading instead of after it.

```html
<flux:accordion variant="reverse">
    <!-- ... -->
</flux:accordion>
```


| Prop | Description |
| --- | --- |
| variant | When set to reverse, displays the icon before the heading instead of after it. |
| transition | If true, enables expanding transitions for smoother interactions. Default: false. |
| exclusive | If true, only one accordion item can be expanded at a time. Default: false. |


| Prop | Description |
| --- | --- |
| heading | Shorthand for flux:accordion.heading content. |
| expanded | If true, the accordion item is expanded by default. Default: false. |
| disabled | If true, the accordion item cannot be expanded or collapsed. Default: false. |


| Slot | Description |
| --- | --- |
| default | The heading text. |


| Slot | Description |
| --- | --- |
| default | The content to display when the accordion item is expanded. |



---

## Autocomplete

Enhance an input field with autocomplete suggestions.

```html
<flux:autocomplete wire:model="state" label="State of residence">
    <flux:autocomplete.item>Alabama</flux:autocomplete.item>
    <flux:autocomplete.item>Arkansas</flux:autocomplete.item>
    <flux:autocomplete.item>California</flux:autocomplete.item>
    <!-- ... -->
</flux:autocomplete>
```


| Prop | Description |
| --- | --- |
| wire:model | The name of the Livewire property to bind the input value to. |
| type | HTML input type (e.g., text, email, password, file, date). Default: text. |
| label | Label text displayed above the input. |
| description | Descriptive text displayed below the label. |
| placeholder | Placeholder text displayed when the input is empty. |
| size | Size of the input. Options: sm, xs. |
| variant | Visual style variant. Options: filled. Default: outline. |
| disabled | If true, prevents user interaction with the input. |
| readonly | If true, makes the input read-only. |
| invalid | If true, applies error styling to the input. |
| multiple | For file inputs, allows selecting multiple files. |
| mask | Input mask pattern using Alpine's mask plugin. Example: 99/99/9999. |
| icon | Name of the icon displayed at the start of the input. |
| icon:trailing | Name of the icon displayed at the end of the input. |
| kbd | Keyboard shortcut hint displayed at the end of the input. |
| clearable | If true, displays a clear button when the input has content. |
| copyable | If true, displays a copy button to copy the input's content. |
| viewable | For password inputs, if true, displays a toggle to show/hide the password. |
| as | Render the input as a different element. Options: button. Default: input. |
| class:input | CSS classes applied directly to the input element instead of the wrapper. |


| Slot | Description |
| --- | --- |
| icon | Custom content displayed at the start of the input (e.g., icons). |
| icon:leading | Custom content displayed at the start of the input (e.g., icons). |
| icon:trailing | Custom content displayed at the end of the input (e.g., buttons). |


| Prop | Description |
| --- | --- |
| value | The value to be set when this item is selected. If not provided, the item's text content is used. |
| disabled | If present or true, the item cannot be selected. |



---

## Avatar

Display an image or initials as an avatar.

```html
<flux:avatar src="https://unavatar.io/x/calebporzio" />
```

### Tooltip
Use the tooltip prop to display a tooltip when hovering over the avatar.

```html
<flux:avatar tooltip="Caleb Porzio" src="https://unavatar.io/x/calebporzio" />

<!-- Or infer from the name prop... -->
<flux:avatar tooltip name="Caleb Porzio" src="https://unavatar.io/x/calebporzio" />
```

### Initials
When no src is provided, the name prop will be used to automatically generate initials. You can also use the initials prop directly.

```html
<flux:avatar name="Caleb Porzio" />
<flux:avatar name="calebporzio" />
<flux:avatar name="calebporzio" initials:single />

<!-- Or use the initials prop directly... -->
<flux:avatar initials="CP" />
```

### Size
Use the size prop to change the size of the avatar.

```html
<!-- Extra large: size-16 (64px) -->
<flux:avatar size="xl" src="https://unavatar.io/x/calebporzio" />

<!-- Large: size-12 (48px) -->
<flux:avatar size="lg" src="https://unavatar.io/x/calebporzio" />

<!-- Default: size-10 (40px) -->
<flux:avatar src="https://unavatar.io/x/calebporzio" />

<!-- Small: size-8 (32px) -->
<flux:avatar size="sm" src="https://unavatar.io/x/calebporzio" />

<!-- Extra small: size-6 (24px) -->
<flux:avatar size="xs" src="https://unavatar.io/x/calebporzio" />
```

### Icon
Use the icon prop to display an icon instead of an image.

```html
<flux:avatar icon="user" />
<flux:avatar icon="phone" />
<flux:avatar icon="computer-desktop" />
```

### Colors
Use the color prop to change the color of the avatar.

```html
<flux:avatar name="Caleb Porzio" color="red" />
<flux:avatar name="Caleb Porzio" color="orange" />
<flux:avatar name="Caleb Porzio" color="amber" />
<flux:avatar name="Caleb Porzio" color="yellow" />
<flux:avatar name="Caleb Porzio" color="lime" />
<flux:avatar name="Caleb Porzio" color="green" />
<flux:avatar name="Caleb Porzio" color="emerald" />
<flux:avatar name="Caleb Porzio" color="teal" />
<flux:avatar name="Caleb Porzio" color="cyan" />
<flux:avatar name="Caleb Porzio" color="sky" />
<flux:avatar name="Caleb Porzio" color="blue" />
<flux:avatar name="Caleb Porzio" color="indigo" />
<flux:avatar name="Caleb Porzio" color="violet" />
<flux:avatar name="Caleb Porzio" color="purple" />
<flux:avatar name="Caleb Porzio" color="fuchsia" />
<flux:avatar name="Caleb Porzio" color="pink" />
<flux:avatar name="Caleb Porzio" color="rose" />
```

### Auto color
Deterministically generate a color based on a user's name.

```html
<flux:avatar name="Caleb Porzio" color="auto" />

<!-- Use color:seed to generate a consistent color based -->
<!-- on something unchanging like a user's ID... -->
<flux:avatar name="Caleb Porzio" color="auto" color:seed="{{ $user->id }}" />
```

### Circle
Use the circle prop to make the avatar circular.

```html
<flux:avatar circle src="https://unavatar.io/x/calebporzio" />
```

### Badge
Add badges to avatars in various ways. Use the badge prop by itself for a simple dot indicator, provide content like numbers or emojis, or even pass in custom HTML via a slot.

```html
<flux:avatar badge badge:color="green" src="https://unavatar.io/x/calebporzio" />

<flux:avatar badge badge:color="zinc" badge:position="top right" badge:circle badge:variant="outline" src="https://unavatar.io/x/calebporzio" />

<flux:avatar badge="25" src="https://unavatar.io/x/calebporzio" />

<flux:avatar circle badge="👍" badge:circle src="https://unavatar.io/x/calebporzio" />

<flux:avatar circle src="https://unavatar.io/x/calebporzio">
    <x-slot:badge>
        <img class="size-3" src="https://unavatar.io/github/hugosaintemarie" />
    </x-slot:badge>
</flux:avatar>
```

### Groups
Stack avatars together. By default, grouped avatars have rings that adapt to your theme - white in light mode and a dark color in dark mode. If you need to customize the ring color, to match a different background, you can do so by adding a custom class to the flux:avatar.group component.

```html
<flux:avatar.group>
    <flux:avatar src="https://unavatar.io/x/calebporzio" />
    <flux:avatar src="https://unavatar.io/github/hugosaintemarie" />
    <flux:avatar src="https://unavatar.io/github/joshhanley" />
    <flux:avatar>3+</flux:avatar>
</flux:avatar.group>

<!-- Adapt rings to custom background... -->
<flux:avatar.group class="**:ring-zinc-100 dark:**:ring-zinc-800">
    <flux:avatar circle src="https://unavatar.io/x/calebporzio" />
    <flux:avatar circle src="https://unavatar.io/github/hugosaintemarie" />
    <flux:avatar circle src="https://unavatar.io/github/joshhanley" />
    <flux:avatar circle>3+</flux:avatar>
</flux:avatar.group>
```

### As button
Use the as prop to make the avatar a button.

```html
<flux:avatar as="button" src="https://unavatar.io/x/calebporzio" />
```

### As link
Use the href prop to make the avatar a link.

```html
<flux:avatar href="https://x.com/calebporzio" src="https://unavatar.io/x/calebporzio" />
```


| Caleb Porzio 
        You
    
[email protected] | Admin
Member
Guest |
| Hugo Sainte-Marie
[email protected] | Admin
Member
Guest |
| Josh Hanley
[email protected] | Admin
Member
Guest |


| Prop | Description |
| --- | --- |
| name | User's name to display as initials. If provided without initials, this will be used to generate initials automatically. |
| src | URL to the image to display as avatar. |
| initials | Custom initials to display when no src is provided. Will override name if provided. |
| alt | Alternative text for the avatar image. (Default: name if provided) |
| size | Size of the avatar. Options: xs (24px), sm (32px), (default: 40px), lg (48px). |
| color | Background color when displaying initials or icons. Options: red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose, auto. Default: none (uses system colors). |
| color:seed | Value used when color="auto" to deterministically generate consistent colors. Useful for using user IDs to generate consistent colors. |
| circle | If present or true, makes the avatar fully circular instead of rounded corners. |
| icon | Name of the icon to display instead of an image or initials. |
| icon:variant | Icon variant to use. Options: outline, solid. Default: solid. |
| tooltip | Text to display in a tooltip when hovering over the avatar. If set to true, uses the name prop as tooltip content. |
| tooltip:position | Position of the tooltip. Options: top, right, bottom, left. Default: top. |
| badge | Content to display as a badge. Can be a string, boolean, or a slot. |
| badge:color | Color of the badge. Options: same color options as the color prop. |
| badge:circle | If present or true, makes the badge fully circular instead of slightly rounded corners. |
| badge:position | Position of the badge. Options: top left, top right, bottom left, bottom right. Default: bottom right. |
| badge:variant | Variant of the badge. Options: solid, outline. Default: solid. |
| as | Element to render the avatar as. Options: button, div (default). |
| href | URL to link to, making the avatar a link element. |


| Slot | Description |
| --- | --- |
| default | Custom content to display inside the avatar. Will override initials if provided. |
| badge | Custom content to display in the badge (for more complex badge content). |


| Prop | Description |
| --- | --- |
| class | CSS classes to apply to the group, including customizing ring colors using *:ring-{color} format. |


| Slot | Description |
| --- | --- |
| default | Place multiple flux:avatar components here to display them as a group. |



---

## Badge

Highlight information like status, category, or count.

```html
<flux:badge color="lime">New</flux:badge>
```

### Sizes
Choose between three different sizes for your badges with the size prop.

```html
<flux:badge size="sm">Small</flux:badge>
<flux:badge>Default</flux:badge>
<flux:badge size="lg">Large</flux:badge>
```

### Icons
Add icons to badges with the icon and icon:trailing props.

```html
<flux:badge icon="user-circle">Users</flux:badge>
<flux:badge icon="document-text">Files</flux:badge>
<flux:badge icon:trailing="video-camera">Videos</flux:badge>
```

### Pill variant
Display badges with a fully rounded border radius using the variant="pill" prop.

```html
<flux:badge variant="pill" icon="user">Users</flux:badge>
```

### As button
Make the entire badge clickable by wrapping it in a button element.

```html
<flux:badge as="button" variant="pill" icon="plus" size="lg">Amount</flux:badge>
```

### With close button
Make a badge removable by appending a close button.

```html
<flux:badge>
    Admin <flux:badge.close />
</flux:badge>
```

### Colors
Choose from an array of colors to differentiate between badges and convey emotion.

```html
<flux:badge color="zinc">Zinc</flux:badge>
<flux:badge color="red">Red</flux:badge>
<flux:badge color="orange">Orange</flux:badge>
<flux:badge color="amber">Amber</flux:badge>
<flux:badge color="yellow">Yellow</flux:badge>
<flux:badge color="lime">Lime</flux:badge>
<flux:badge color="green">Green</flux:badge>
<flux:badge color="emerald">Emerald</flux:badge>
<flux:badge color="teal">Teal</flux:badge>
<flux:badge color="cyan">Cyan</flux:badge>
<flux:badge color="sky">Sky</flux:badge>
<flux:badge color="blue">Blue</flux:badge>
<flux:badge color="indigo">Indigo</flux:badge>
<flux:badge color="violet">Violet</flux:badge>
<flux:badge color="purple">Purple</flux:badge>
<flux:badge color="fuchsia">Fuchsia</flux:badge>
<flux:badge color="pink">Pink</flux:badge>
<flux:badge color="rose">Rose</flux:badge>
```

### Solid variant
Bold, high-contrast badges for more important status indicators or alerts.

```html
<flux:badge variant="solid" color="zinc">Zinc</flux:badge>
<flux:badge variant="solid" color="red">Red</flux:badge>
<flux:badge variant="solid" color="orange">Orange</flux:badge>
<flux:badge variant="solid" color="amber">Amber</flux:badge>
<flux:badge variant="solid" color="yellow">Yellow</flux:badge>
<flux:badge variant="solid" color="lime">Lime</flux:badge>
<flux:badge variant="solid" color="green">Green</flux:badge>
<flux:badge variant="solid" color="emerald">Emerald</flux:badge>
<flux:badge variant="solid" color="teal">Teal</flux:badge>
<flux:badge variant="solid" color="cyan">Cyan</flux:badge>
<flux:badge variant="solid" color="sky">Sky</flux:badge>
<flux:badge variant="solid" color="blue">Blue</flux:badge>
<flux:badge variant="solid" color="indigo">Indigo</flux:badge>
<flux:badge variant="solid" color="violet">Violet</flux:badge>
<flux:badge variant="solid" color="purple">Purple</flux:badge>
<flux:badge variant="solid" color="fuchsia">Fuchsia</flux:badge>
<flux:badge variant="solid" color="pink">Pink</flux:badge>
<flux:badge variant="solid" color="rose">Rose</flux:badge>
```

### Inset
If you're using badges alongside inline text, you might run into spacing issues because of the extra padding around the badge. Use the inset prop to add negative margins to the top and bottom of the badge to avoid this.

```html
<flux:heading>
    Page builder <flux:badge color="lime" inset="top bottom">New</flux:badge>
</flux:heading>

<flux:text class="mt-2">Easily author new pages without leaving your browser.</flux:text>
```


| Prop | Description |
| --- | --- |
| color | Badge color (e.g., zinc, red, blue). Default: zinc. |
| size | Badge size. Options: sm, lg. |
| variant | Badge style variant. Options: pill. |
| icon | Name of the icon to display before the badge text. |
| icon:trailing | Name of the icon to display after the badge text. |
| icon:variant | Icon variant. Options: outline, solid, mini, micro. Default: mini. |
| as | HTML element to render the badge as. Options: button. Default: div. |
| inset | Add negative margins to specific sides. Options: top, bottom, left, right, or any combination of the four. |


| Prop | Description |
| --- | --- |
| icon | Name of the icon to display. Default: x-mark. |
| icon:variant | Icon variant. Options: outline, solid, mini, micro. Default: mini. |



---

## Brand

Display your company or application's logo and name in a clean, consistent way across your interface.

```html
<flux:brand href="#" logo="/img/demo/logo.png" name="Acme Inc." />

<flux:brand href="#" name="Acme Inc.">
    <x-slot name="logo">
        <div class="size-6 rounded shrink-0 bg-accent text-accent-foreground flex items-center justify-center"><i class="font-serif font-bold">A</i></div>
    </x-slot>
</flux:brand>
```

### Logo slot
Use the logo slot to provide a custom logo for your brand.

```html
<flux:brand href="#" name="Launchpad">
    <x-slot name="logo" class="size-6 rounded-full bg-cyan-500 text-white text-xs font-bold">
        <flux:icon name="rocket-launch" variant="micro" />
    </x-slot>
</flux:brand>
```

### Logo only
Display just the logo without the company name by omitting the name prop.

```html
<flux:brand href="#" logo="/img/demo/logo.png" />
```


| Prop | Description |
| --- | --- |
| name | Company or application name to display next to the logo. |
| logo | URL to the image to display as logo, or can pass content via slot. |
| alt | Alternative text for the logo. |
| href | URL to navigate to when the brand is clicked. Default: '/'. |


| Slot | Description |
| --- | --- |
| logo | Custom content for the logo section, typically containing an image, SVG, or custom HTML. |



---

## Button

A powerful and composable button component for your application.

```html
<flux:button>Button</flux:button>
```

### Variants
Use the variant prop to change the visual style of the button.

```html
<flux:button>Default</flux:button>
<flux:button variant="primary">Primary</flux:button>
<flux:button variant="filled">Filled</flux:button>
<flux:button variant="danger">Danger</flux:button>
<flux:button variant="ghost">Ghost</flux:button>
<flux:button variant="subtle">Subtle</flux:button>
```

### Sizes
The default button size works great for most cases, but here are some additional size options for unique situations.

```html
<flux:button>Base</flux:button>
<flux:button size="sm">Small</flux:button>
<flux:button size="xs">Extra small</flux:button>
```

### Icons
Automatically sized and styled icons for your buttons.

```html
<flux:button icon="ellipsis-horizontal" />
<flux:button icon="arrow-down-tray">Export</flux:button>
<flux:button icon:trailing="chevron-down">Open</flux:button>
<flux:button icon="x-mark" variant="subtle" />
```

### Loading
Buttons with wire:click or type="submit" will automatically show a loading indicator and disable pointer events during network requests.

```html
<flux:button wire:click="save">
    Save changes
</flux:button>
```

### Full width
A button that spans the full width of the container.

```html
<flux:button variant="primary" class="w-full">Send invite</flux:button>
```

### Button groups
Fuse related buttons into a group with shared borders.

```html
<flux:button.group>
    <flux:button>Oldest</flux:button>
    <flux:button>Newest</flux:button>
    <flux:button>Top</flux:button>
</flux:button.group>
```

### Icon group
Fuse multiple icon buttons into a visually-linked group.

```html
<flux:button.group>
    <flux:button icon="bars-3-bottom-left"></flux:button>
    <flux:button icon="bars-3"></flux:button>
    <flux:button icon="bars-3-bottom-right"></flux:button>
</flux:button.group>
```

### Attached button
Append or prepend an icon button to another button to add additional functionality.

```html
<flux:button.group>
    <flux:button>New product</flux:button>
    <flux:button icon="chevron-down"></flux:button>
</flux:button.group>
```

### As a link
Display an HTML a tag as a button by passing the href prop.

```html
<flux:button
    href="https://google.com"
    icon:trailing="arrow-up-right"
>
    Visit Google
</flux:button>
```

### As an input
To display a button as an input, pass as="button" to the input component.

```html
<flux:input as="button" placeholder="Search..." icon="magnifying-glass" kbd="⌘K" />
```

### Square
Make the height and width of a button equal. Flux does this automatically for icon-only buttons.

```html
<flux:button square>...</flux:button>
```

### Inset
When using ghost or subtle button variants, you can use the inset prop to negate any invisible padding for better alignment.

```html
<div class="flex justify-between">
    <flux:heading>Post successfully created.</flux:heading>

    <flux:button size="sm" icon="x-mark" variant="ghost" inset />
</div>
```


| Prop | Description |
| --- | --- |
| as | The HTML tag to render the button as. Options: button (default), a, div. |
| href | The URL to link to when the button is used as an anchor tag. |
| type | The HTML type attribute of the button. Options: button (default), submit. |
| variant | Visual style of the button. Options: outline, primary, filled, danger, ghost, subtle. Default: outline. |
| size | Size of the button. Options: base (default), sm, xs. |
| icon | Name of the icon to display at the start of the button. |
| icon:variant | Visual style of the icon. Options: outline (default), solid, mini, micro. |
| icon:trailing | Name of the icon to display at the end of the button. |
| square | If true, makes the button square. (Useful for icon-only buttons.) |
| inset | Add negative margins to specific sides. Options: top, bottom, left, right, or any combination of the four. |
| loading | If true, shows a loading spinner and disables the button when used with wire:click or type="submit". If false, the button will not show a loading spinner at all. Default: true. |
| tooltip | Text to display in a tooltip when hovering over the button. |
| tooltip:position | Position of the tooltip. Options: top, bottom, left, right. Default: top. |
| tooltip:kbd | Text to display in a keyboard shortcut tooltip when hovering over the button. |
| kbd | Text to display in a keyboard shortcut tooltip when hovering over the button. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the button. Common use: w-full for full width. |


| Attribute | Description |
| --- | --- |
| data-flux-button | Applied to the root element for styling and identification. |


| Slot | Description |
| --- | --- |
| default | The buttons to be grouped together. |



---

## Breadcrumbs

Help users navigate and understand their place within your application.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

### With slashes
Use slashes instead of chevrons to separate breadcrumb items.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" separator="slash">Home</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="#" separator="slash">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item separator="slash">Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

### With icon
Use an icon instead of text for a particular breadcrumb item.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item href="#">Blog</flux:breadcrumbs.item>
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

### With ellipsis
Truncate a long breadcrumb list with an ellipsis.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />
    <flux:breadcrumbs.item icon="ellipsis-horizontal" />
    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```

### With ellipsis dropdown
Truncate a long breadcrumb list into a single ellipsis dropdown.

```html
<flux:breadcrumbs>
    <flux:breadcrumbs.item href="#" icon="home" />

    <flux:breadcrumbs.item>
        <flux:dropdown>
            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />

            <flux:navmenu>
                <flux:navmenu.item>Client</flux:navmenu.item>
                <flux:navmenu.item icon="arrow-turn-down-right">Team</flux:navmenu.item>
                <flux:navmenu.item icon="arrow-turn-down-right">User</flux:navmenu.item>
            </flux:navmenu>
        </flux:dropdown>
    </flux:breadcrumbs.item>

    <flux:breadcrumbs.item>Post</flux:breadcrumbs.item>
</flux:breadcrumbs>
```


| Slot | Description |
| --- | --- |
| default | The breadcrumb items to display. |


| Prop | Description |
| --- | --- |
| href | URL the breadcrumb item links to. If omitted, renders as non-clickable text. |
| icon | Name of the icon to display before the badge text. |
| icon:variant | Icon variant. Options: outline, solid, mini, micro. Default: mini. |
| separator | Name of the icon to display as the separator. Default: chevron-right. |



---

## Calendar

A flexible calendar component for date selection. Supports single dates, multiple dates, and date ranges. Perfect for scheduling and booking systems.

```html
<flux:calendar />
```

### Basic Usage
Select multiple non-consecutive dates.

```html
<flux:calendar value="2025-06-04" />
```

### Multiple dates
Select multiple non-consecutive dates.

```html
<flux:calendar multiple />
```

### Date range
Select a range of dates.

```html
<flux:calendar mode="range" />
```

### Range Configuration
Adjust the calendar's size to fit your layout. Available sizes include xs, sm, lg, xl, and 2xl.

```html
<!-- Set minimum and maximum range limits -->
<flux:calendar mode="range" min-range="3" max-range="10" />

<!-- Control number of months shown -->
<flux:calendar mode="range" months="2" />
```

### Size
Adjust the calendar's size to fit your layout. Available sizes include xs, sm, lg, xl, and 2xl.

```html
<flux:calendar size="xl" />
```

### Static
Create a non-interactive calendar for display purposes.

```html
<flux:calendar
    static
    value="2025-06-04"
    size="xs"
    :navigation="false"
/>
```

### Min/max dates
Restrict the selectable date range by setting minimum and maximum boundaries.

```html
<flux:calendar max="2025-06-04" />
```

### Unavailable dates
Disable specific dates from being selected. Useful for blocking out holidays, showing booked dates, or indicating unavailable time slots.

```html
<flux:calendar unavailable="2025-06-03,2025-06-05" />
```

### With today shortcut
Add a shortcut button to quickly navigate to today's date. When viewing a different month, it jumps to the current month. When already viewing the current month, it selects today's date.

```html
<flux:calendar with-today />
```

### Selectable header
Enable quick navigation by making the month and year in the header selectable.

```html
<flux:calendar selectable-header />
```

### Fixed weeks
Display a consistent number of weeks in every month. Prevents layout shifts when navigating between months with different numbers of weeks.

```html
<flux:calendar fixed-weeks />
```

### Start day
By default, the first day of the week will be automatically set based on the user's locale. You can override this by setting the start-day attribute to any day of the week.

```html
<flux:calendar start-day="1" />
```

### Open to
Set the date that the calendar will open to. Otherwise, the calendar defaults to the selected date's month, or the current month.

```html
<flux:calendar open-to="2026-07-01" />
```

### Week numbers
Display the week number for each week.

```html
<flux:calendar week-numbers />
```

### Localization
By default, the calendar will use the browser's locale (e.g. navigator.language).

```html
<flux:calendar locale="ja-JP" />
```

### The DateRange object
A specialized object for handling date ranges when using `mode="range"`.

```html
<flux:calendar wire:model.live="range" />
```

### Instantiation
A specialized object for handling date ranges when using `mode="range"`.

```html
__OPENPHP__

use Livewire\Component;
use Flux\DateRange;

class Dashboard extends Component {
    public DateRange $range;

    public function mount() {
        $this->range = new DateRange(now(), now()->addDays(7));
    }
}
```

### Persisting to the session
A specialized object for handling date ranges when using `mode="range"`.

```html
__OPENPHP__

use Livewire\Attributes\Session;
use Livewire\Component;
use Flux\DateRange;

class Dashboard extends Component {
    #[Session]
    public DateRange $range;
}
```

### Using with Eloquent
A specialized object for handling date ranges when using `mode="range"`.

```html
__OPENPHP__

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Order;
use Flux\DateRange;

class Dashboard extends Component {
    public ?DateRange $range;

    #[Computed]
    public function orders() {
        return $this->range
            ? Order::whereBetween('created_at', $this->range)->get()
            : Order::all();
    }
}
```

### Available methods
A specialized object for handling date ranges when using `mode="range"`.

```html
$range = new Flux\DateRange(
    now()->subDays(1),
    now()->addDays(1),
);

// Get the start and end dates as Carbon instances...
$start = $range->start();
$end = $range->end();

// Check if the range contains a date...
$range->contains(now());

// Get the number of days in the range...
$range->length();

// Loop over the range by day...
foreach ($range as $date) {
    // $date is a Carbon instance...
}

// Get the range as an array of Carbon instances representing each day in the range...
$range->toArray();
```


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |  |
| --- | --- |
|  |  |


|  |
| --- |
|  |


| Prop | Description |
| --- | --- |
| wire:model | Binds the calendar to a Livewire property. See the wire:model documentation for more information. |
| value | Selected date(s). Format depends on mode: single date (Y-m-d), multiple dates (comma-separated Y-m-d), or range (Y-m-d/Y-m-d). |
| mode | Selection mode. Options: single (default), multiple, range. |
| min | Earliest selectable date. Can be a date string or "today". |
| max | Latest selectable date. Can be a date string or "today". |
| size | Calendar size. Options: base (default), xs, sm, lg, xl, 2xl. |
| months | Number of months to display. Default: 1 for single/multiple modes, 2 for range mode. |
| min-range | Minimum number of days that can be selected in range mode. |
| max-range | Maximum number of days that can be selected in range mode. |
| navigation | If false, hides month navigation controls. Default: true. |
| static | If true, makes the calendar non-interactive (display-only). Default: false. |
| multiple | If true, enables multiple date selection mode. Default: false. |
| week-numbers | If true, displays week numbers in the calendar. Default: false. |
| selectable-header | If true, displays month and year dropdowns for quick navigation. Default: false. |
| with-today | If true, displays a button to quickly navigate to today's date. Default: false. |
| with-inputs | If true, displays date inputs at the top of the calendar for manual date entry. Default: false. |
| locale | Set the locale for the calendar. Examples: fr, en-US, ja-JP. |


| Attribute | Description |
| --- | --- |
| data-flux-calendar | Applied to the root element for styling and identification. |


| Method | Description |
| --- | --- |
| $range->start() | Get the start date as a Carbon instance. |
| $range->end() | Get the end date as a Carbon instance. |
| $range->days() | Get the number of days in the range. |
| $range->contains(date) | Check if the range contains a specific date. |
| $range->length() | Get the length of the range in days. |
| $range->toArray() | Get the range as an array with start and end keys. |
| $range->preset() | Get the current preset as a DateRangePreset enum value, if any. |


| Static Method | Description |
| --- | --- |
| DateRange::today() | Create a DateRange for today. |
| DateRange::yesterday() | Create a DateRange for yesterday. |
| DateRange::thisWeek() | Create a DateRange for the current week. |
| DateRange::lastWeek() | Create a DateRange for the previous week. |
| DateRange::last7Days() | Create a DateRange for the last 7 days. |
| DateRange::thisMonth() | Create a DateRange for the current month. |
| DateRange::lastMonth() | Create a DateRange for the previous month. |
| DateRange::thisYear() | Create a DateRange for the current year. |
| DateRange::lastYear() | Create a DateRange for the previous year. |
| DateRange::yearToDate() | Create a DateRange from January 1st to today. |



---

## Callout

Highlight important information or guide users toward key actions.

```html
<flux:callout icon="clock">
    <flux:callout.heading>Upcoming maintenance</flux:callout.heading>

    <flux:callout.text>
        Our servers will be undergoing scheduled maintenance this Sunday from 2 AM - 5 AM UTC. Some services may be temporarily unavailable.
        <flux:callout.link href="#">Learn more</flux:callout.link>
    </flux:callout.text>
</flux:callout>
```

### Icon inside heading
For a more compact layout, place the icon inside the heading by adding the icon prop to flux:callout.heading instead of the root flux:callout component.

```html
<flux:callout>
    <flux:callout.heading icon="newspaper">Policy update</flux:callout.heading>

    <flux:callout.text>We've updated our Terms of Service and Privacy Policy. Please review them to stay informed.</flux:callout.text>
</flux:callout>
```

### With actions
Add action buttons to your callout to provide users with clear next steps.

```html
<flux:callout icon="clock">
    <flux:callout.heading>Subscription expiring soon</flux:callout.heading>
    <flux:callout.text>Your current plan will expire in 3 days. Renew now to avoid service interruption and continue accessing premium features.</flux:callout.text>

    <x-slot name="actions">
        <flux:button>Renew now</flux:button>
        <flux:button variant="ghost" href="/pricing">View plans</flux:button>
    </x-slot>
</flux:callout>
```

### Inline actions
Use the inline prop to display actions inline with the callout.

```html
<flux:callout icon="cube" variant="secondary" inline>
    <flux:callout.heading>Your package is delayed</flux:callout.heading>

    <x-slot name="actions">
        <flux:button>Track order -></flux:button>
        <flux:button variant="ghost">Reschedule</flux:button>
    </x-slot>
</flux:callout>

<flux:callout icon="exclamation-triangle" variant="secondary" inline>
    <flux:callout.heading>Payment issue detected</flux:callout.heading>
    <flux:callout.text>Your last payment attempt failed. Update your billing details to prevent service interruption.</flux:callout.text>

    <x-slot name="actions">
        <flux:button>Update billing</flux:button>
    </x-slot>
</flux:callout>
```

### Dismissible
Add a close button, using the controls slot, to allow users to dismiss callouts they no longer care to see.

```html
<flux:callout icon="bell" variant="secondary" inline x-data="{ visible: true }" x-show="visible">
    <flux:callout.heading class="flex gap-2 @max-md:flex-col items-start">Upcoming meeting <flux:text>10:00 AM</flux:text></flux:callout.heading>

    <x-slot name="controls">
        <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
    </x-slot>
</flux:callout>

<!-- Wrapping divs to add smooth exist transition... -->
<div x-data="{ visible: true }" x-show="visible" x-collapse>
    <div x-show="visible" x-transition>
        <flux:callout icon="finger-print" variant="secondary">
            <flux:callout.heading>Unusual login attempt</flux:callout.heading>
            <flux:callout.text>We detected a login from a new device in <span class="font-medium text-zinc-800 dark:text-white">New York, USA</span>. If this was you, no action is needed. If not, secure your account immediately.</flux:callout.text>

            <x-slot name="actions">
                <flux:button>Change password</flux:button>
                <flux:button variant="ghost">Review activity</flux:button>
            </x-slot>

            <x-slot name="controls">
                <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
            </x-slot>
        </flux:callout>
    </div>
</div>
```

### Variants
Use predefined variants to convey a specific tone or level of urgency.

```html
<flux:callout variant="secondary" icon="information-circle" heading="Your account has been successfully created." />
<flux:callout variant="success" icon="check-circle" heading="Your account is verified and ready to use." />
<flux:callout variant="warning" icon="exclamation-circle" heading="Please verify your account to unlock all features." />
<flux:callout variant="danger" icon="x-circle" heading="Something went wrong. Try again or contact support." />
```

### Colors
Use the color prop to change the color of the callout to match your use case.

```html
<flux:callout color="zinc" ... />
<flux:callout color="red" ... />
<flux:callout color="orange" ... />
<flux:callout color="amber" ... />
<flux:callout color="yellow" ... />
<flux:callout color="lime" ... />
<flux:callout color="green" ... />
<flux:callout color="emerald" ... />
<flux:callout color="teal" ... />
<flux:callout color="cyan" ... />
<flux:callout color="sky" ... />
<flux:callout color="blue" ... />
<flux:callout color="indigo" ... />
<flux:callout color="violet" ... />
<flux:callout color="purple" ... />
<flux:callout color="fuchsia" ... />
<flux:callout color="pink" ... />
<flux:callout color="rose" ... />
```

### Custom icon
Use a custom icon to match your brand or specific use case using the icon slot.

```html
<flux:callout>
    <x-slot name="icon">
        <!-- Custom icon: https://lucide.dev/icons/alarm-clock -->
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alarm-clock"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/><path d="M6.38 18.7 4 21"/><path d="M17.64 18.67 20 21"/></svg>
    </x-slot>

    <flux:callout.heading>Notification system updated</flux:callout.heading>

    <flux:callout.text>
        <p>We've improved our notification system to deliver alerts faster and more reliably.</p>
    </flux:callout.text>
</flux:callout>
```


| Prop | Description |
| --- | --- |
| icon | Name of the icon displayed next to the heading (e.g., clock). Explore icons |
| icon:variant | Variant of the icon displayed next to the heading (e.g., outline). Explore icon variants |
| variant | Options: secondary, success, warning, danger. Default: secondary |
| color | Custom color (e.g., red, blue). View available Tailwind colors -> |
| inline | If true, actions appear inline. Default: false. |
| heading | Shorthand for flux:callout.heading. |
| text | Shorthand for flux:callout.text. |


| Slot | Description |
| --- | --- |
| icon | Custom icon displayed next to the heading. |
| actions | Buttons or links inside the callout (flux:callout.button). |
| controls | Extra UI elements placed at the top right of the callout (e.g., close button). |


| Prop | Description |
| --- | --- |
| icon | Moves the icon inside the heading instead of the callout root. |
| icon:variant | Variant of the icon displayed next to the heading (e.g., outline). Explore icon variants |


| Slot | Description |
| --- | --- |
| icon | Custom icon displayed next to the heading. |


| Slot | Description |
| --- | --- |
| default | Text content inside the callout. |


| Prop | Description |
| --- | --- |
| href | The URL the link points to. |
| external | If true, the link opens in a new tab. Default: false. |



---

## Card

A container for related content, such as a form, alert, or data list.

```html
<flux:card class="space-y-6">
    <div>
        <flux:heading size="lg">Log in to your account</flux:heading>
        <flux:text class="mt-2">Welcome back!</flux:text>
    </div>

    <div class="space-y-6">
        <flux:input label="Email" type="email" placeholder="Your email address" />

        <flux:field>
            <div class="mb-3 flex justify-between">
                <flux:label>Password</flux:label>

                <flux:link href="#" variant="subtle" class="text-sm">Forgot password?</flux:link>
            </div>

            <flux:input type="password" placeholder="Your password" />

            <flux:error name="password" />
        </flux:field>
    </div>

    <div class="space-y-2">
        <flux:button variant="primary" class="w-full">Log in</flux:button>

        <flux:button variant="ghost" class="w-full">Sign up for a new account</flux:button>
    </div>
</flux:card>
```

### Small card
Use the small card variant for compact content like notifications, alerts, or brief summaries.

```html
<a href="#" aria-label="Latest on our blog">
    <flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
        <flux:heading class="flex items-center gap-2">Latest on our blog <flux:icon name="arrow-up-right" class="ml-auto text-zinc-400" variant="micro" /></flux:heading>
        <flux:text class="mt-2">Stay up to date with our latest insights, tutorials, and product updates.</flux:text>
    </flux:card>
</a>
```

### Header actions
Use the button component to add actions to the header.

```html
<flux:card class="space-y-6">
    <div class="flex">
        <div class="flex-1">
            <flux:heading size="lg">Are you sure?</flux:heading>

            <flux:text class="mt-2">
                <p>Your post will be deleted permanently.</p>
                <p>This action cannot be undone.</p>
            </flux:text>
        </div>

        <div class="-mx-2 -mt-2">
            <flux:button variant="ghost" size="sm" icon="x-mark" inset="top right bottom" />
        </div>
    </div>

    <div class="flex gap-4">
        <flux:spacer />
        <flux:button variant="ghost">Undo</flux:button>
        <flux:button variant="danger">Delete</flux:button>
    </div>
</flux:card>
```

### Simple card
Let's be honest, a card is just a div with a border and some padding.

```html
<flux:card>
    <flux:heading size="lg">Are you sure?</flux:heading>

    <flux:text class="mt-2 mb-4">
        <p>Your post will be deleted permanently.</p>
        <p>This action cannot be undone.</p>
    </flux:text>

    <flux:button variant="danger">Delete</flux:button>
</flux:card>
```


| Slot | Description |
| --- | --- |
| default | Content to display within the card. Can include headings, text, forms, buttons, and other components. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the card. Common uses: space-y-6 for spacing between child elements, max-w-md for width control, p-0 to remove padding. |


| Attribute | Description |
| --- | --- |
| data-flux-card | Applied to the root element for styling and identification. |



---

## Chart

Flux's Chart component is a lightweight, zero-dependency tool for building charts in your Livewire applications. It is designed to be simple but extremely flexible, so that you can assemble and style your charts exactly as you need them.

```html
<flux:chart wire:model="data" class="aspect-3/1">
    <flux:chart.svg>
        <flux:chart.line field="visitors" class="text-pink-500 dark:text-pink-400" />

        <flux:chart.axis axis="x" field="date">
            <flux:chart.axis.line />
            <flux:chart.axis.tick />
        </flux:chart.axis>

        <flux:chart.axis axis="y">
            <flux:chart.axis.grid />
            <flux:chart.axis.tick />
        </flux:chart.axis>

        <flux:chart.cursor />
    </flux:chart.svg>

    <flux:chart.tooltip>
        <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric']" />
        <flux:chart.tooltip.value field="visitors" label="Visitors" />
    </flux:chart.tooltip>
</flux:chart>
```

### Data structure
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
__OPENPHP__

use Livewire\Component;

class Dashboard extends Component
{
    public array $data = [
        ['date' => '2025-06-04', 'visitors' => 267],
        ['date' => '2025-06-03', 'visitors' => 259],
        ['date' => '2025-06-02', 'visitors' => 269],
        // ...
    ];
}
```

### Examples
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data" class="aspect-[3/1]">
    <flux:chart.svg>
        <flux:chart.line field="memory" class="text-pink-500" />
        <flux:chart.point field="memory" class="text-pink-400" />

        <flux:chart.axis axis="x" field="date">
            <flux:chart.axis.tick />
            <flux:chart.axis.line />
        </flux:chart.axis>

        <flux:chart.axis axis="y" tick-values="[0, 128, 256, 384, 512]" :format="['style' => 'unit', 'unit' => 'megabyte']">
            <flux:chart.axis.grid />
            <flux:chart.axis.tick />
        </flux:chart.axis>
    </flux:chart.svg>
</flux:chart>
```

### Line chart
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data" class="aspect-[3/1]">
    <flux:chart.svg>
        <flux:chart.line field="memory" class="text-pink-500" />
        <flux:chart.point field="memory" class="text-pink-400" />

        <flux:chart.axis axis="x" field="date">
            <flux:chart.axis.tick />
            <flux:chart.axis.line />
        </flux:chart.axis>

        <flux:chart.axis axis="y" tick-values="[0, 128, 256, 384, 512]" :format="['style' => 'unit', 'unit' => 'megabyte']">
            <flux:chart.axis.grid />
            <flux:chart.axis.tick />
        </flux:chart.axis>
    </flux:chart.svg>
</flux:chart>
```

### Area chart
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data" class="aspect-3/1">
    <flux:chart.svg>
        <flux:chart.line field="stock" class="text-blue-500 dark:text-blue-400" curve="none" />
        <flux:chart.area field="stock" class="text-blue-200/50 dark:text-blue-400/30" curve="none" />

        <flux:chart.axis axis="y" position="right" tick-prefix="$" :format="[
            'notation' => 'compact',
            'compactDisplay' => 'short',
            'maximumFractionDigits' => 1,
        ]">
            <flux:chart.axis.grid />
            <flux:chart.axis.tick />
        </flux:chart.axis>

        <flux:chart.axis axis="x" field="date">
            <flux:chart.axis.tick />
            <flux:chart.axis.line />
        </flux:chart.axis>
    </flux:chart.svg>
</flux:chart>
```

### Multiple lines
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data">
    <flux:chart.viewport class="min-h-[20rem]" >
        <flux:chart.svg>
            <flux:chart.line field="twitter" class="text-blue-500" curve="none" />
            <flux:chart.point field="twitter" class="text-blue-500" r="6" stroke-width="3" />
            <flux:chart.line field="facebook" class="text-red-500" curve="none" />
            <flux:chart.point field="facebook" class="text-red-500" r="6" stroke-width="3" />
            <flux:chart.line field="instagram" class="text-green-500" curve="none" />
            <flux:chart.point field="instagram" class="text-green-500" r="6" stroke-width="3" />

            <flux:chart.axis axis="x" field="date">
                <flux:chart.axis.tick />
                <flux:chart.axis.line />
            </flux:chart.axis>

            <flux:chart.axis axis="y" tick-start="0" tick-end="1" :format="[
                'style' => 'percent',
                'minimumFractionDigits' => 0,
                'maximumFractionDigits' => 0,
            ]">
                <flux:chart.axis.grid />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>
    </flux:chart.viewport>

    <div class="flex justify-center gap-4 pt-4">
        <flux:chart.legend label="Instagram">
            <flux:chart.legend.indicator class="bg-green-400" />
        </flux:chart.legend>

        <flux:chart.legend label="Twitter">
            <flux:chart.legend.indicator class="bg-blue-400" />
        </flux:chart.legend>

        <flux:chart.legend label="Facebook">
            <flux:chart.legend.indicator class="bg-red-400" />
        </flux:chart.legend>
    </div>
</flux:chart>
```

### Live summary
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:card>
    <flux:chart class="grid gap-6" wire:model="data">
        <flux:chart.summary class="flex gap-12">
            <div>
                <flux:text>Today</flux:text>

                <flux:heading size="xl" class="mt-2 tabular-nums">
                    <flux:chart.summary.value field="sales" :format="['style' => 'currency', 'currency' => 'USD']" />
                </flux:heading>

                <flux:text class="mt-2 tabular-nums">
                    <flux:chart.summary.value field="date" :format="['hour' => 'numeric', 'minute' => 'numeric', 'hour12' => true]" />
                </flux:text>
            </div>

            <div>
                <flux:text>Yesterday</flux:text>

                <flux:heading size="lg" class="mt-2 tabular-nums">
                    <flux:chart.summary.value field="yesterday" :format="['style' => 'currency', 'currency' => 'USD']" />
                </flux:heading>
            </div>
        </flux:chart.summary>

        <flux:chart.viewport class="aspect-[3/1]">
            <flux:chart.svg>
                <flux:chart.line field="yesterday" class="text-zinc-300 dark:text-white/40" stroke-dasharray="4 4" curve="none" />
                <flux:chart.line field="sales" class="text-sky-500 dark:text-sky-400" curve="none" />

                <flux:chart.axis axis="x" field="date">
                    <flux:chart.axis.grid />
                    <flux:chart.axis.tick />
                    <flux:chart.axis.line />
                </flux:chart.axis>

                <flux:chart.axis axis="y">
                    <flux:chart.axis.tick />
                </flux:chart.axis>

                <flux:chart.cursor />
            </flux:chart.svg>
        </flux:chart.viewport>
    </flux:chart>
</flux:card>
```

### Sparkline
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart :value="[15, 18, 16, 19, 22, 25, 28, 25, 29, 28, 32, 35]" class="w-[5rem] aspect-[3/1]">
    <flux:chart.svg gutter="0">
        <flux:chart.line class="text-green-500 dark:text-green-400" />
    </flux:chart.svg>
</flux:chart>
```

### Dashboard stat
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:card class="overflow-hidden min-w-[12rem]">
    <flux:text>Revenue</flux:text>

    <flux:heading size="xl" class="mt-2 tabular-nums">$12,345</flux:heading>

    <flux:chart class="-mx-8 -mb-8 h-[3rem]" :value="[10, 12, 11, 13, 15, 14, 16, 18, 17, 19, 21, 20]">
        <flux:chart.svg gutter="0">
            <flux:chart.line class="text-sky-200 dark:text-sky-400" />
            <flux:chart.area class="text-sky-100 dark:text-sky-400/30" />
        </flux:chart.svg>
    </flux:chart>
</flux:card>
```

### Chart padding
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart>
    <flux:chart.svg gutter="12 0 12 8">
        <!-- ... -->
    </flux:chart.svg>
</flux:chart>
```

### Axis scale
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.axis axis="y" scale="linear">
    <!-- ... -->
</flux:chart.axis>
```

### Axis lines
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <flux:chart.axis axis="x">
        <!-- Horizontal "X" axis line: -->
        <flux:chart.axis.line />
    </flux:chart.axis>

    <flux:chart.axis axis="y">
        <!-- Vertical "Y" axis line: -->
        <flux:chart.axis.line />
    </flux:chart.axis>
</flux:chart.svg>
```

### Zero line
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <!-- Zero line: -->
    <flux:chart.zero-line />
</flux:chart.svg>
```

### Grid lines
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <flux:chart.axis axis="x">
        <!-- Vertical grid lines: -->
        <flux:chart.axis.grid />
    </flux:chart.axis>

    <flux:chart.axis axis="y">
        <!-- Horizontal grid lines: -->
        <flux:chart.axis.grid />
    </flux:chart.axis>
</flux:chart.svg>
```

### Ticks
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <flux:chart.axis axis="x">
        <!-- X axis tick mark lines: -->
        <flux:chart.axis.mark />

        <!-- X axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>

    <flux:chart.axis axis="y">
        <!-- Y axis tick mark lines: -->
        <flux:chart.axis.mark />

        <!-- Y axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
</flux:chart.svg>
```

### Tick frequency
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.axis axis="y" tick-count="5">
    <!-- ... -->
</flux:chart.axis>
```

### Tick formatting
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <!-- Format the X axis tick labels to display the month and day: -->
    <flux:chart.axis axis="x" :format="['month' => 'long', 'day' => 'numeric']">
        <!-- X axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>

    <!-- Format the Y axis tick labels to display the value in USD: -->
    <flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD']">
        <!-- Y axis tick labels: -->
        <flux:chart.axis.tick />
    </flux:chart.axis>
</flux:chart.svg>
```

### Cursor
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.svg>
    <!-- ... -->

    <flux:chart.cursor />
</flux:chart.svg>
```

### Tooltip
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart>
    <flux:chart.svg>
        <!-- ... -->
    </flux:chart.svg>

    <flux:chart.tooltip>
        <flux:chart.tooltip.heading field="date" />

        <flux:chart.tooltip.value field="visitors" label="Visitors" />
        <flux:chart.tooltip.value field="views" label="Views" :format="['notation' => 'compact']" />
    </flux:chart.tooltip>
</flux:chart>
```

### Legend
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data">
    <flux:chart.viewport class="aspect-3/1">
        <flux:chart.svg>
            <flux:chart.line class="text-blue-500" field="visitors" />
            <flux:chart.line class="text-red-500" field="views" />
        </flux:chart.svg>
    </flux:chart.viewport>

    <div class="flex justify-center gap-4 pt-4">
        <flux:chart.legend label="Visitors">
            <flux:chart.legend.indicator class="bg-blue-400" />
        </flux:chart.legend>

        <flux:chart.legend label="Views">
            <flux:chart.legend.indicator class="bg-red-400" />
        </flux:chart.legend>
    </div>
</flux:chart>
```

### Summary
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart wire:model="data">
    <flux:chart.summary>
        <flux:chart.summary.value field="visitors" :format="['notation' => 'compact']" />
    </flux:chart.summary>

    <flux:chart.viewport class="aspect-[3/1]">
        <!-- ... -->
    </flux:chart.viewport>
</flux:chart>
```

### Formatting
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD']" />
```

### Formatting numbers
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD']" />
```

### Formatting dates
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.

```html
<flux:chart.axis axis="x" field="date" :format="['month' => 'long', 'day' => 'numeric']" />
```

### Additional resources
Container for the chart's SVG elements. This component must be included within a `flux:chart` to render the chart.


| Stock | Price | Change | Trend |
| --- | --- | --- | --- |
| AAPL | $193.45 | +2.4% |  |
| MSFT | $338.12 | +1.8% |  |
| TSLA | $242.68 | -3.2% |  |
| GOOGL | $129.87 | +0.9% |  |


| Description | Example Input | Example Output | Format Array |
| --- | --- | --- | --- |
| Currency (USD) | 1234.56 |  | ['style' => 'currency', 'currency' => 'USD'] |
| Currency (EUR) | 1234.56 |  | ['style' => 'currency', 'currency' => 'EUR'] |
| Percent | 0.85 |  | ['style' => 'percent'] |
| Compact Number | 1000000 |  | ['notation' => 'compact'] |
| Scientific | 123456789 |  | ['notation' => 'scientific'] |
| Fixed Decimal | 3.1415926535 |  | ['maximumFractionDigits' => 2] |
| Thousands Separator | 1234567 |  | ['useGrouping' => true] |
| Custom Unit | 50 |  | ['style' => 'unit', 'unit' => 'megabyte'] |


| Description | Example Input | Example Output | Format Array |
| --- | --- | --- | --- |
| Full Date | 2024-03-15 |  | ['dateStyle' => 'full'] |
| Month and Day | 2024-03-15 |  | ['month' => 'long', 'day' => 'numeric'] |
| Short Month | 2024-03-15 |  | ['month' => 'short', 'day' => 'numeric'] |
| Time Only | 2024-03-15 14:30 |  | ['hour' => 'numeric', 'minute' => 'numeric', 'hour12' => true] |
| 24-hour Time | 2024-03-15 14:30 |  | ['hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false] |
| Weekday | 2024-03-15 |  | ['weekday' => 'long'] |
| Short Date | 2024-03-15 |  | ['month' => '2-digit', 'day' => '2-digit', 'year' => '2-digit'] |
| Year Only | 2024-03-15 |  | ['year' => 'numeric'] |


| Prop | Description |
| --- | --- |
| wire:model | Binds the chart to a Livewire property containing the data to display. See the wire:model documentation for more information. |
| value | Array of data points for the chart. Each point should be an associative array with named fields. Used when not binding with wire:model. |
| curve | Default line curve type for all lines in the chart. Options: smooth (default), none. |


| Slot | Description |
| --- | --- |
| default | Chart components to render. Typically contains a flux:chart.svg component that defines the chart structure. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the chart container. Common uses: aspect-3/1 for aspect ratio, h-64 for fixed height. |


| Attribute | Description |
| --- | --- |
| data-flux-chart | Applied to the root element for styling and identification. |


| Slot | Description |
| --- | --- |
| default | Chart visualization components like lines, areas, axes, and interactive elements. |


| Prop | Description |
| --- | --- |
| field | Name of the data field to plot on the y-axis. Required. |
| curve | Line curve type. Options: smooth (default), none. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the line. Common use: text-{color} for line color. |


| Prop | Description |
| --- | --- |
| field | Name of the data field to plot on the y-axis. Required. |
| curve | Area curve type. Options: smooth (default), none. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the area. Common use: fill-{color}/20 for fill color. |


| Prop | Description |
| --- | --- |
| field | Name of the data field to plot points for. Required. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the points. Common use: fill-{color} for point fill color, r attribute for point radius. |


| Prop | Description |
| --- | --- |
| axis | Axis to configure. Options: x, y. Required. |
| field | For x-axis, the data field to use for labels. |
| format | Date/number formatting options for axis labels. See Formatting for more details. |


| Prop | Description |
| --- | --- |
| position | Position of the tick marks. Options: top, bottom, left, right. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the tick marks. Common use: text-{color} for line color, stroke-width="1" for line thickness. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the axis line. Common use: text-{color} for line color, stroke-width="1" for line thickness. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the gridlines. Common use: text-{color} for line color, stroke-width="{width}" for line thickness. |


| Prop | Description |
| --- | --- |
| format | Date/number formatting options for tick labels. See Formatting for more details. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the tick marks and labels. Common use: text-{color} for label color, font-{weight} for label weight. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the zero line. Common use: text-{color} for line color, stroke-width="{width}" for line thickness. |


| Prop | Description |
| --- | --- |
| field | Data field to display in the tooltip. |
| format | Date/number formatting options for tooltip values. |


| Prop | Description |
| --- | --- |
| field | Data field to display in the tooltip heading. |
| format | Date/number formatting options for tooltip values. See Formatting for more details. |


| Prop | Description |
| --- | --- |
| field | Data field to display in the tooltip. |
| format | Date/number formatting options for tooltip values. See Formatting for more details. |


| CSS | Description |
| --- | --- |
| class | Additional CSS classes applied to the cursor line. |


| Slot | Description |
| --- | --- |
| default | Content to display in the summary section. Can include flux:chart.value components to display specific data values. |


| Prop | Description |
| --- | --- |
| field | Data field to display. |
| fallback | Fallback text to display if the value is not found or cannot be formatted. |
| format | Date/number formatting options. See Formatting for more details. |


| Prop | Description |
| --- | --- |
| field | Data field this legend item represents. |
| label | Label text for the legend item. |
| format | Date/number formatting options. See Formatting for more details. |


| Slot | Description |
| --- | --- |
| default | Content to display in the legend. Can include arbitrary content, including flux:chart.legend.indicator components. |



---

## Checkbox

Select one or multiple options from a set.

```html
<flux:field variant="inline">
    <flux:checkbox wire:model="terms" />
    
    <flux:label>I agree to the terms and conditions</flux:label>

    <flux:error name="terms" />
</flux:field>
```

### Checkbox group
Organize a list of related checkboxes vertically.

```html
<flux:checkbox.group wire:model="notifications" label="Notifications">
    <flux:checkbox label="Push notifications" value="push" checked />
    <flux:checkbox label="Email" value="email" checked />
    <flux:checkbox label="In-app alerts" value="app" />
    <flux:checkbox label="SMS" value="sms" />
</flux:checkbox.group>
```

### With descriptions
Align descriptions for each checkbox directly below its label.

```html
<flux:checkbox.group wire:model="subscription" label="Subscription preferences">
    <flux:checkbox checked
        value="newsletter"
        label="Newsletter"
        description="Receive our monthly newsletter with the latest updates and offers."
    />
    <flux:checkbox
        value="updates"
        label="Product updates"
        description="Stay informed about new features and product updates."
    />
    <flux:checkbox
        value="invitations"
        label="Event invitations"
        description="Get invitations to our exclusive events and webinars."
    />
</flux:checkbox.group>
```

### Horizontal fieldset
Organize a group of related checkboxes horizontally.

```html
<flux:fieldset>
    <flux:legend>Languages</flux:legend>

    <flux:description>Choose the languages you want to support.</flux:description>

    <div class="flex gap-4 *:gap-x-2">
        <flux:checkbox checked value="english" label="English" />
        <flux:checkbox checked value="spanish" label="Spanish" />
        <flux:checkbox value="french" label="French" />
        <flux:checkbox value="german" label="German" />
    </div>
</flux:fieldset>
```

### Check-all
Control a group of checkboxes with a single checkbox.

```html
<flux:checkbox.group>
    <flux:checkbox.all />

    <flux:checkbox checked />
    <flux:checkbox />
    <flux:checkbox />
</flux:checkbox.group>
```

### Checked
Mark a checkbox as checked by default.

```html
<flux:checkbox checked />
```

### Disabled
Prevent users from interacting with and modifying a checkbox.

```html
<flux:checkbox disabled />
```

### Checkbox cards
A bordered alternative to standard checkboxes.

```html
<flux:checkbox.group wire:model="subscription" label="Subscription preferences" variant="cards" class="max-sm:flex-col">
    <flux:checkbox checked
        value="newsletter"
        label="Newsletter"
        description="Get the latest updates and offers."
    />
    <flux:checkbox
        value="updates"
        label="Product updates"
        description="Learn about new features and products."
    />
    <flux:checkbox
        value="invitations"
        label="Event invitations"
        description="Invitatations to exclusive events."
    />
</flux:checkbox.group>
```

### Vertical cards
You can arrange a set of checkbox cards vertically by simply adding the flex-col class to the group container.

```html
<flux:checkbox.group label="Subscription preferences" variant="cards" class="flex-col">
    <flux:checkbox checked
        value="newsletter"
        label="Newsletter"
        description="Get the latest updates and offers."
    />
    <flux:checkbox
        value="updates"
        label="Product updates"
        description="Learn about new features and products."
    />
    <flux:checkbox
        value="invitations"
        label="Event invitations"
        description="Invitatations to exclusive events."
    />
</flux:checkbox.group>
```

### Cards with icons
You can arrange a set of checkbox cards vertically by simply adding the flex-col class to the group container.

```html
<flux:checkbox.group label="Subscription preferences" variant="cards" class="flex-col">
    <flux:checkbox checked
        value="newsletter"
        icon="newspaper"
        label="Newsletter"
        description="Get the latest updates and offers."
    />
    <flux:checkbox
        value="updates"
        icon="cube"
        label="Product updates"
        description="Learn about new features and products."
    />
    <flux:checkbox
        value="invitations"
        icon="calendar"
        label="Event invitations"
        description="Invitatations to exclusive events."
    />
</flux:checkbox.group>
```

### Custom card content
You can compose your own custom cards through the flux:checkbox component slot.

```html
<flux:checkbox.group label="Subscription preferences" variant="cards" class="flex-col">
    <flux:checkbox checked value="newsletter">
        <flux:checkbox.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Newsletter</flux:heading>
            <flux:text size="sm" class="mt-2">Get the latest updates and offers.</flux:text>
        </div>
    </flux:checkbox>

    <flux:checkbox value="updates">
        <flux:checkbox.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Product updates</flux:heading>
            <flux:text size="sm" class="mt-2">Learn about new features and products.</flux:text>
        </div>
    </flux:checkbox>

    <flux:checkbox value="invitations">
        <flux:checkbox.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Event invitations</flux:heading>
            <flux:text size="sm" class="mt-2">Invitatations to exclusive events.</flux:text>
        </div>
    </flux:checkbox>
</flux:checkbox.group>
```


|  | Name |
| --- | --- |
|  | Caleb Porzio |
|  | Hugo Sainte-Marie |
|  | Keith Damiani |


| Prop | Description |
| --- | --- |
| wire:model | Binds the checkbox to a Livewire property. See the wire:model documentation for more information. |
| label | Label text displayed next to the checkbox. When provided, wraps the checkbox in a structure with an adjacent label. |
| description | Help text displayed below the checkbox. When provided alongside label, appears between the label and checkbox. |
| value | Value associated with the checkbox when used in a group. When the checkbox is checked, this value will be included in the array returned by the group's wire:model. |
| checked | Sets the checkbox to be checked by default. |
| indeterminate | Sets the checkbox to an indeterminate state, represented by a dash instead of a checkmark. Useful for "select all" checkboxes when only some items are selected. |
| disabled | Prevents user interaction with the checkbox. |
| invalid | Applies error styling to the checkbox. |


| Attribute | Description |
| --- | --- |
| data-flux-checkbox | Applied to the root element for styling and identification. |
| data-checked | Applied when the checkbox is checked. |
| data-indeterminate | Applied when the checkbox is in an indeterminate state. |


| Prop | Description |
| --- | --- |
| wire:model | Binds the checkbox group to a Livewire property. The value will be an array of the selected checkboxes' values. See the wire:model documentation for more information. |
| label | Label text displayed above the checkbox group. When provided, wraps the group in a flux:field component with an adjacent flux:label component. |
| description | Help text displayed below the group label. When provided alongside label, appears between the label and the checkboxes. |
| variant | Visual style of the group. Options: default, cards (Pro). |
| disabled | Prevents user interaction with all checkboxes in the group. |
| invalid | Applies error styling to all checkboxes in the group. |


| Slot | Description |
| --- | --- |
| default | The checkboxes to be grouped together. Can include flux:checkbox, flux:checkbox.all, and other elements. |


| Prop | Description |
| --- | --- |
| label | Text label displayed next to the checkbox. |
| description | Help text displayed below the checkbox. |
| disabled | Prevents user interaction with the checkbox. |



---

## Command

A searchable list of commands.

```html
<flux:command>
    <flux:command.input placeholder="Search..." />

    <flux:command.items>
        <flux:command.item wire:click="..." icon="user-plus" kbd="⌘A">Assign to…</flux:command.item>
        <flux:command.item wire:click="..." icon="document-plus">Create new file</flux:command.item>
        <flux:command.item wire:click="..." icon="folder-plus" kbd="⌘⇧N">Create new project</flux:command.item>
        <flux:command.item wire:click="..." icon="book-open">Documentation</flux:command.item>
        <flux:command.item wire:click="..." icon="newspaper">Changelog</flux:command.item>
        <flux:command.item wire:click="..." icon="cog-6-tooth" kbd="⌘,">Settings</flux:command.item>
    </flux:command.items>
</flux:command>
```

### As a modal
Open a command palette as a modal for quick access to frequently used commands.

```html
<flux:modal.trigger name="search" shortcut="cmd.k">
    <flux:input as="button" placeholder="Search..." icon="magnifying-glass" kbd="⌘K" />
</flux:modal.trigger>

<flux:modal name="search" variant="bare" class="w-full max-w-[30rem] my-[12vh] max-h-screen overflow-y-hidden">
    <flux:command class="border-none shadow-lg inline-flex flex-col max-h-[76vh]">
        <flux:command.input placeholder="Search..." closable />

        <flux:command.items>
            <flux:command.item icon="user-plus" kbd="⌘A">Assign to…</flux:command.item>
            <flux:command.item icon="document-plus">Create new file</flux:command.item>
            <flux:command.item icon="folder-plus" kbd="⌘⇧N">Create new project</flux:command.item>
            <flux:command.item icon="book-open">Documentation</flux:command.item>
            <flux:command.item icon="newspaper">Changelog</flux:command.item>
            <flux:command.item icon="cog-6-tooth" kbd="⌘,">Settings</flux:command.item>
        </flux:command.items>
    </flux:command>
</flux:modal>
```


| Attribute | Description |
| --- | --- |
| data-flux-command | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| clearable | If true, displays a clear button when the input has content. |
| closable | If true, displays a close button to dismiss the command palette. |
| icon | Name of the icon displayed at the start of the input. Default: magnifying-glass. |
| placeholder | Placeholder text displayed when the input is empty. |


| Prop | Description |
| --- | --- |
| icon | Name of the icon displayed at the start of the item. |
| icon:variant | Visual style of the icon. Options: outline (default), solid, mini, micro. |
| kbd | Keyboard shortcut hint displayed at the end of the item (e.g., ⌘K). |


| Attribute | Description |
| --- | --- |
| data-flux-command-item | Applied to the item element for styling and identification. |



---

## Context

Dropdown menus that open when right clicking a designated area.

```html
<flux:context>
    <flux:card class="border-dashed border-2 px-16">
        <flux:text>Right click</flux:text>
    </flux:card>

    <flux:menu>
        <flux:menu.item icon="plus">New post</flux:menu.item>

        <flux:menu.separator />

        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio.group>
                <flux:menu.radio checked>Name</flux:menu.radio>
                <flux:menu.radio>Date</flux:menu.radio>
                <flux:menu.radio>Popularity</flux:menu.radio>
            </flux:menu.radio.group>
        </flux:menu.submenu>

        <flux:menu.submenu heading="Filter">
            <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
            <flux:menu.checkbox checked>Published</flux:menu.checkbox>
            <flux:menu.checkbox>Archived</flux:menu.checkbox>
        </flux:menu.submenu>

        <flux:menu.separator />

        <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
    </flux:menu>
</flux:context>
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the context menu's state to a Livewire property. See the wire:model documentation for more information. |
| position | Controls the position of the menu relative to the click position. Format: [vertical] [horizontal]. Vertical options: top, bottom (default). Horizontal options: start, center, end (default). |
| gap | Distance in pixels between the menu and the click position. Default: 4. |
| offset | Additional offset in pixels along both axes. Format: [x] [y]. |
| target | ID of an external element to use as the menu. Use this when you need the menu to be outside the context element's DOM tree. |
| detail | Custom value to be stored in the menu's data-detail attribute, useful for adding custom styling or behavior based on the source of the context menu. |
| disabled | Prevents the context menu from being shown when right-clicking. |


| Slot | Description |
| --- | --- |
| default | The first child element functions as the trigger area, which will show the context menu when right-clicked. The second child element should be a flux:menu component that will appear as the context menu. |



---

## Date picker

Allow users to select dates or date ranges via a calendar overlay. Perfect for filtering data or scheduling events.

```html
<flux:date-picker />
```

### Basic usage
Attach the date picker to a date input for more precise date selection control.

```html
<flux:date-picker value="2025-06-04" />
```

### Input trigger
Attach the date picker to a date input for more precise date selection control.

```html
<flux:date-picker wire:model="date">
    <x-slot name="trigger">
        <flux:date-picker.input />
    </x-slot>
</flux:date-picker>
```

### Range picker
Enable selection of date ranges for reporting, booking systems, or any scenario requiring a start and end date.

```html
<flux:date-picker mode="range" />
```

### Range limits
Control the allowed range of dates that can be selected.

```html
<flux:date-picker mode="range" min-range="3" />
```

### Range with inputs
Use separate inputs for start and end dates to provide a clearer interface for date range selection.

```html
<flux:date-picker mode="range">
    <x-slot name="trigger">
        <div class="flex flex-col sm:flex-row gap-6 sm:gap-4">
            <flux:date-picker.input label="Start" />
            <flux:date-picker.input label="End" />
        </div>
    </x-slot>
</flux:date-picker>
```

### Presets
Allow users to select from frequently used ranges like "Last 7 days" or "This month".

```html
<flux:date-picker mode="range" with-presets />
```

### Available presets
Add a shortcut button to quickly navigate to today's date. When viewing a different month, it jumps to the current month. When already viewing the current month, it selects today's date.

```html
<flux:date-picker
    mode="range"
    presets="... allTime"
    :min="auth()->user()->created_at->format('Y-m-d')"
/>
```

### All time
Add a shortcut button to quickly navigate to today's date. When viewing a different month, it jumps to the current month. When already viewing the current month, it selects today's date.

```html
<flux:date-picker
    mode="range"
    presets="... allTime"
    :min="auth()->user()->created_at->format('Y-m-d')"
/>
```

### Custom range preset
Add a shortcut button to quickly navigate to today's date. When viewing a different month, it jumps to the current month. When already viewing the current month, it selects today's date.

```html
<flux:date-picker mode="range" presets="... custom" />
```

### With today shortcut
Add a shortcut button to quickly navigate to today's date. When viewing a different month, it jumps to the current month. When already viewing the current month, it selects today's date.

```html
<flux:date-picker with-today />
```

### Selectable header
Enable quick navigation by making the month and year in the header selectable.

```html
<flux:date-picker selectable-header />
```

### Fixed weeks
Display a consistent number of weeks in every month. Prevents layout shifts when navigating between months with different numbers of weeks.

```html
<flux:date-picker fixed-weeks />
```

### Start day
By deafult the first day of the week will be automatically set based on the user's locale. You can override this by setting the start-day attribute to any day of the week.

```html
<flux:date-picker start-day="1" />
```

### Open to
Set the date that the calendar will open to. Otherwise, the calendar defaults to the selected date's month, or the current month.

```html
<flux:date-picker open-to="2026-07-01" />
```

### Week numbers
Display the week number for each week.

```html
<flux:date-picker week-numbers />
```

### Localization
By default, the date picker will use the browser's locale (e.g. navigator.language).

```html
<flux:date-picker locale="ja-JP" />
```

### The DateRange object
A specialized object for handling date ranges when using mode="range".

```html
<flux:calendar wire:model.live="range" />
```

### Instantiation
A specialized object for handling date ranges when using mode="range".

```html
__OPENPHP__

use Livewire\Component;
use Flux\DateRange;

class Dashboard extends Component {
    public DateRange $range;

    public function mount() {
        $this->range = new DateRange(now(), now()->addDays(7));
    }
}
```

### Persisting to the session
A specialized object for handling date ranges when using mode="range".

```html
__OPENPHP__

use Livewire\Attributes\Session;
use Livewire\Component;
use Flux\DateRange;

class Dashboard extends Component {
    #[Session]
    public DateRange $range;
}
```

### Using with Eloquent
A specialized object for handling date ranges when using mode="range".

```html
__OPENPHP__

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Order;
use Flux\DateRange;

class Dashboard extends Component {
    public ?DateRange $range;

    #[Computed]
    public function orders() {
        return $this->range
            ? Order::whereBetween('created_at', $this->range)->get()
            : Order::all();
    }
}
```

### Available methods
A specialized object for handling date ranges when using mode="range".

```html
$range = new Flux\DateRange(
    now()->subDays(1),
    now()->addDays(1),
);

// Get the start and end dates as Carbon instances...
$start = $range->start();
$end = $range->end();

// Check if the range contains a date...
$range->contains(now());

// Get the number of days in the range...
$range->length();

// Loop over the range by day...
foreach ($range as $date) {
    // $date is a Carbon instance...
}

// Get the range as an array of Carbon instances representing each day in the range...
$range->toArray();
```

### Range presets
A specialized object for handling date ranges when using mode="range".

```html
[
    'start' => null,
    'end' => null,
    'preset' => 'lastMonth',
]
```


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


| Key | Label | Constructor | Date Range |
| --- | --- | --- | --- |
| today | Today | DateRange::today() | Current day |
| yesterday | Yesterday | DateRange::yesterday() | Previous day |
| thisWeek | This Week | DateRange::thisWeek() | Current week |
| lastWeek | Last Week | DateRange::lastWeek() | Previous week |
| last7Days | Last 7 Days | DateRange::last7Days() | Previous 7 days |
| thisMonth | This Month | DateRange::thisMonth() | Current month |
| lastMonth | Last Month | DateRange::lastMonth() | Previous month |
| thisQuarter | This Quarter | DateRange::thisQuarter() | Current quarter |
| lastQuarter | Last Quarter | DateRange::lastQuarter() | Previous quarter |
| thisYear | This Year | DateRange::thisYear() | Current year |
| lastYear | Last Year | DateRange::lastYear() | Previous year |
| last14Days | Last 14 Days | DateRange::last14Days() | Previous 14 days |
| last30Days | Last 30 Days | DateRange::last30Days() | Previous 30 days |
| last3Months | Last 3 Months | DateRange::last3Months() | Previous 3 months |
| last6Months | Last 6 Months | DateRange::last6Months() | Previous 6 months |
| yearToDate | Year to Date | DateRange::yearToDate() | January 1st to today |
| allTime | All Time | DateRange::allTime($start) | Minimum supplied date to today |
| custom | Custom Range | DateRange::custom() | User-defined date range |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |
| --- |
|  |


|  |  |
| --- | --- |
|  |  |


|  |
| --- |
|  |


| Prop | Description |
| --- | --- |
| wire:model | Binds the date picker to a Livewire property. See the wire:model documentation for more information. |
| value | Selected date(s). Format depends on mode: single date (Y-m-d) or range (Y-m-d/Y-m-d). |
| mode | Selection mode. Options: single (default), range. |
| min-range | Minimum number of days that can be selected in range mode. |
| max-range | Maximum number of days that can be selected in range mode. |
| min | Earliest selectable date. Can be a date string or "today". |
| max | Latest selectable date. Can be a date string or "today". |
| months | Number of months to display. Default: 1 in single mode, 2 in range mode. |
| label | Label text displayed above the date picker. When provided, wraps the component in a flux:field with an adjacent flux:label. |
| description | Help text displayed below the date picker. When provided alongside label, appears between the label and date picker within the flux:field wrapper. |
| description:trailing | The description provided will be displayed below the date picker instead of above it. |
| badge | Badge text displayed at the end of the flux:label component when the label prop is provided. |
| placeholder | Placeholder text displayed when no date is selected. Default depends on mode. |
| size | Size of the calendar day cells. Options: sm, default, lg, xl, 2xl. |
| week-numbers | If true, displays week numbers in the calendar. |
| selectable-header | If true, displays month and year dropdowns for quick navigation. |
| with-today | If true, displays a button to quickly navigate to today's date. |
| with-inputs | If true, displays date inputs at the top of the calendar for manual date entry. |
| with-confirmation | If true, requires confirmation before applying the selected date(s). |
| with-presets | If true, displays preset date ranges. Use with presets to customize available options. |
| presets | Space-separated list of preset date ranges to display. Default: today yesterday thisWeek last7Days thisMonth yearToDate allTime. |
| clearable | Displays a clear button when a date is selected. |
| disabled | Prevents user interaction with the date picker. |
| invalid | Applies error styling to the date picker. |
| locale | Set the locale for the date picker. Examples: fr, en-US, ja-JP. |


| Slot | Description |
| --- | --- |
| trigger | Custom trigger element to open the date picker. Usually a flux:date-picker.input or flux:date-picker.button. |


| Attribute | Description |
| --- | --- |
| data-flux-date-picker | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| label | Label text displayed above the input. When provided, wraps the input in a flux:field component with an adjacent flux:label component. |
| description | Help text displayed below the input. When provided alongside label, appears between the label and input within the flux:field wrapper. |
| placeholder | Placeholder text displayed when no date is selected. |
| clearable | Displays a clear button when a date is selected. |
| disabled | Prevents user interaction with the input. |
| invalid | Applies error styling to the input. |


| Prop | Description |
| --- | --- |
| placeholder | Text displayed when no date is selected. |
| size | Size of the button. Options: sm, xs. |
| clearable | Displays a clear button when a date is selected. |
| disabled | Prevents user interaction with the button. |
| invalid | Applies error styling to the button. |


| Method | Description |
| --- | --- |
| $range->start() | Get the start date as a Carbon instance. |
| $range->end() | Get the end date as a Carbon instance. |
| $range->days() | Get the number of days in the range. |
| $range->preset() | Get the current preset as a DateRangePreset enum value. |
| $range->toArray() | Get the range as an array with start and end keys. |


| Static Method | Description |
| --- | --- |
| DateRange::today() | Create a DateRange for today. |
| DateRange::yesterday() | Create a DateRange for yesterday. |
| DateRange::thisWeek() | Create a DateRange for the current week. |
| DateRange::lastWeek() | Create a DateRange for the previous week. |
| DateRange::last7Days() | Create a DateRange for the last 7 days. |
| DateRange::last30Days() | Create a DateRange for the last 30 days. |
| DateRange::thisMonth() | Create a DateRange for the current month. |
| DateRange::lastMonth() | Create a DateRange for the previous month. |
| DateRange::thisQuarter() | Create a DateRange for the current quarter. |
| DateRange::lastQuarter() | Create a DateRange for the previous quarter. |
| DateRange::thisYear() | Create a DateRange for the current year. |
| DateRange::lastYear() | Create a DateRange for the previous year. |
| DateRange::yearToDate() | Create a DateRange from January 1st to today. |
| DateRange::allTime() | Create a DateRange with no limits. |



---

## Dropdown

A composable dropdown component that can handle both simple navigation menus as well as complex action menus with checkboxes, radios, and submenus.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>

    <flux:menu>
        <flux:menu.item icon="plus">New post</flux:menu.item>

        <flux:menu.separator />

        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio.group>
                <flux:menu.radio checked>Name</flux:menu.radio>
                <flux:menu.radio>Date</flux:menu.radio>
                <flux:menu.radio>Popularity</flux:menu.radio>
            </flux:menu.radio.group>
        </flux:menu.submenu>

        <flux:menu.submenu heading="Filter">
            <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
            <flux:menu.checkbox checked>Published</flux:menu.checkbox>
            <flux:menu.checkbox>Archived</flux:menu.checkbox>
        </flux:menu.submenu>

        <flux:menu.separator />

        <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Navigation menus
Display a simple set of links in a dropdown menu.

```html
<flux:dropdown position="bottom" align="end">
    <flux:profile avatar="/img/demo/user.png" name="Olivia Martin" />

    <flux:navmenu>
        <flux:navmenu.item href="#" icon="user">Account</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="building-storefront">Profile</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="credit-card">Billing</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="arrow-right-start-on-rectangle">Logout</flux:navmenu.item>
        <flux:navmenu.item href="#" icon="trash" variant="danger">Delete</flux:navmenu.item>
    </flux:navmenu>
</flux:dropdown>
```

### Positioning
Customize the position of the dropdown menu via the position and align props. You can first pass the base position: top, bottom, left, and right, then an alignment modifier like start, center, or end.

```html
<flux:dropdown position="top" align="start">

<!-- More positions... -->
<flux:dropdown position="right" align="center">
<flux:dropdown position="bottom" align="center">
<flux:dropdown position="left" align="end">
```

### Offset & gap
Customize the offset/gap of the dropdown menu via the offset and gap props. These properties accept values in pixels.

```html
<flux:dropdown offset="-15" gap="2">
```

### Keyboard hints
Add keyboard shortcut hints to menu items to teach users how to navigate your app faster.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>

    <flux:menu>
        <flux:menu.item icon="pencil-square" kbd="⌘S">Save</flux:menu.item>
        <flux:menu.item icon="document-duplicate" kbd="⌘D">Duplicate</flux:menu.item>
        <flux:menu.item icon="trash" variant="danger" kbd="⌘⌫">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Checkbox items
Select one or many menu options.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Permissions</flux:button>

    <flux:menu>
        <flux:menu.checkbox wire:model="read" checked>Read</flux:menu.checkbox>
        <flux:menu.checkbox wire:model="write" checked>Write</flux:menu.checkbox>
        <flux:menu.checkbox wire:model="delete">Delete</flux:menu.checkbox>
    </flux:menu>
</flux:dropdown>
```

### Radio items
Select a single menu option.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Sort by</flux:button>

    <flux:menu>
        <flux:menu.radio.group wire:model="sortBy">
            <flux:menu.radio checked>Latest activity</flux:menu.radio>
            <flux:menu.radio>Date created</flux:menu.radio>
            <flux:menu.radio>Most popular</flux:menu.radio>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
```

### Groups
Visually group related menu items with a separator line.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>

    <flux:menu>
        <flux:menu.item>View</flux:menu.item>
        <flux:menu.item>Transfer</flux:menu.item>

        <flux:menu.separator />

        <flux:menu.item>Publish</flux:menu.item>
        <flux:menu.item>Share</flux:menu.item>

        <flux:menu.separator />

        <flux:menu.item variant="danger">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Groups with headings
Group options under headings to make them more discoverable.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>

    <flux:menu>
        <flux:menu.group heading="Account">
            <flux:menu.item>Profile</flux:menu.item>
            <flux:menu.item>Permissions</flux:menu.item>
        </flux:menu.group>

        <flux:menu.group heading="Billing">
            <flux:menu.item>Transactions</flux:menu.item>
            <flux:menu.item>Payouts</flux:menu.item>
            <flux:menu.item>Refunds</flux:menu.item>
        </flux:menu.group>

        <flux:menu.item>Logout</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Submenus
Nest submenus for more condensed menus.

```html
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>

    <flux:menu>
        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio checked>Name</flux:menu.radio>
            <flux:menu.radio>Date</flux:menu.radio>
            <flux:menu.radio>Popularity</flux:menu.radio>
        </flux:menu.submenu>

        <flux:menu.submenu heading="Filter">
            <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
            <flux:menu.checkbox checked>Published</flux:menu.checkbox>
            <flux:menu.checkbox>Archived</flux:menu.checkbox>
        </flux:menu.submenu>

        <flux:menu.separator />

        <flux:menu.item variant="danger">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```


| Prop | Description |
| --- | --- |
| position | Position of the dropdown menu. Options: top, right, bottom (default), left. |
| align | Alignment of the dropdown menu. Options: start, center, end. Default: start. |
| offset | Offset in pixels from the trigger element. Default: 0. |
| gap | Gap in pixels between trigger and menu. Default: 4. |


| Attribute | Description |
| --- | --- |
| data-flux-dropdown | Applied to the root element for styling and identification. |


| Slot | Description |
| --- | --- |
| default | The menu items, separators, and submenus. |


| Attribute | Description |
| --- | --- |
| data-flux-menu | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| icon | Name of the icon to display at the start of the item. |
| icon:trailing | Name of the icon to display at the end of the item. |
| icon:variant | Variant of the icon. Options: outline, solid, mini, micro. |
| kbd | Keyboard shortcut hint displayed at the end of the item. |
| suffix | Text displayed at the end of the item. |
| variant | Visual style of the item. Options: default, danger. |
| disabled | If true, prevents interaction with the menu item. |


| Attribute | Description |
| --- | --- |
| data-flux-menu-item | Applied to the root element for styling and identification. |
| data-active | Applied when the item is hovered/active. |


| Prop | Description |
| --- | --- |
| heading | Text displayed as the submenu heading. |
| icon | Name of the icon to display at the start of the submenu. |
| icon:trailing | Name of the icon to display at the end of the submenu. |
| icon:variant | Variant of the icon. Options: outline, solid, mini, micro. |


| Slot | Description |
| --- | --- |
| default | The submenu items (checkboxes, radio buttons, etc.). |


| Prop | Description |
| --- | --- |
| wire:model | Binds the checkbox group to a Livewire property. See the wire:model documentation for more information. |


| Slot | Description |
| --- | --- |
| default | The checkboxes. |


| Prop | Description |
| --- | --- |
| wire:model | Binds the checkbox to a Livewire property. See the wire:model documentation for more information. |
| checked | If true, the checkbox is checked by default. |
| disabled | If true, prevents interaction with the checkbox. |


| Attribute | Description |
| --- | --- |
| data-active | Applied when the checkbox is hovered/active. |
| data-checked | Applied when the checkbox is checked. |


| Prop | Description |
| --- | --- |
| wire:model | Binds the radio group to a Livewire property. See the wire:model documentation for more information. |


| Slot | Description |
| --- | --- |
| default | The radio buttons. |


| Prop | Description |
| --- | --- |
| checked | If true, the radio button is selected by default. |
| disabled | If true, prevents interaction with the radio button. |


| Attribute | Description |
| --- | --- |
| data-active | Applied when the radio button is hovered/active. |
| data-checked | Applied when the radio button is selected. |



---

## Editor

A basic rich text editor for your application. Built using ProseMirror and Tiptap.

```html
<flux:editor wire:model="content" label="…" description="…" />
```

### Toolbar
Flux's editor toolbar is both keyboard/screen-reader accessible and completely customizable to suit your application's needs.

```html
<flux:editor toolbar="heading | bold italic underline | align ~ undo redo" />
```

### Configuring items
The following toolbar items are available:

```html
<flux:editor toolbar="heading | bold italic underline | align ~ undo redo" />
```

### Custom items
Here's an example of what a custom "Copy to clipboard" item in a blade file might look like:

```html
- resources
    - views
        - flux
            - editor
                - copy.blade.php
```

### Customization
By default, the editor will have a minimum height of 200px, and a maximum height of 500px. If you want to customize this behavior, you can use Tailwind utilties to target the content slot and set your own min/max height and overflow behavior.

```html
<flux:editor>
    <flux:editor.toolbar>
        <flux:editor.heading />
        <flux:editor.separator />
        <flux:editor.bold />
        <flux:editor.italic />
        <flux:editor.strike />
        <flux:editor.separator />
        <flux:editor.bullet />
        <flux:editor.ordered />
        <flux:editor.blockquote />
        <flux:editor.separator />
        <flux:editor.link />
        <flux:editor.separator />
        <flux:editor.align />

        <flux:editor.spacer />

        <flux:dropdown position="bottom end" offset="-15">
            <flux:editor.button icon="ellipsis-horizontal" tooltip="More" />

            <flux:menu>
                <flux:menu.item wire:click="…" icon="arrow-top-right-on-square">Preview</flux:menu.item>
                <flux:menu.item wire:click="…" icon="arrow-down-tray">Export</flux:menu.item>
                <flux:menu.item wire:click="…" icon="share">Share</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:editor.toolbar>

    <flux:editor.content />
</flux:editor>
```

### Height
By default, the editor will have a minimum height of 200px, and a maximum height of 500px. If you want to customize this behavior, you can use Tailwind utilties to target the content slot and set your own min/max height and overflow behavior.

```html
<flux:editor class="**:data-[slot=content]:min-h-[100px]!" />
```

### Shortcut keys
Flux's editor uses Tiptap's default shortcut keys which are common amongst most rich text editors.

```html
// lang/es.json

{
    "Rich text editor": "Editor de texto enriquecido",
    "Formatting": "Formato",
    "Text": "Texto",
    "Heading 1": "Encabezado 1",
    "Heading 2": "Encabezado 2",
    "Heading 3": "Encabezado 3",
    "Styles": "Estilos",
    "Bold": "Negrita",
    "Italic": "Cursiva",
    "Underline": "Subrayado",
    "Strikethrough": "Tachado",
    "Subscript": "Subíndice",
    "Superscript": "Superíndice",
    "Highlight": "Resaltar",
    "Code": "Código",
    "Bullet list": "Lista con viñetas",
    "Ordered list": "Lista numerada",
    "Blockquote": "Cita",
    "Insert link": "Insertar enlace",
    "Unlink": "Quitar enlace",
    "Align": "Alinear",
    "Left": "Izquierda",
    "Center": "Centro",
    "Right": "Derecha",
    "Undo": "Deshacer",
    "Redo": "Rehacer"
}
```

### Markdown syntax
Although Flux's editor isn't a markdown editor itself, it allows you to use markdown syntax to trigger styling changes while authoring your content.

```html
// lang/es.json

{
    "Rich text editor": "Editor de texto enriquecido",
    "Formatting": "Formato",
    "Text": "Texto",
    "Heading 1": "Encabezado 1",
    "Heading 2": "Encabezado 2",
    "Heading 3": "Encabezado 3",
    "Styles": "Estilos",
    "Bold": "Negrita",
    "Italic": "Cursiva",
    "Underline": "Subrayado",
    "Strikethrough": "Tachado",
    "Subscript": "Subíndice",
    "Superscript": "Superíndice",
    "Highlight": "Resaltar",
    "Code": "Código",
    "Bullet list": "Lista con viñetas",
    "Ordered list": "Lista numerada",
    "Blockquote": "Cita",
    "Insert link": "Insertar enlace",
    "Unlink": "Quitar enlace",
    "Align": "Alinear",
    "Left": "Izquierda",
    "Center": "Centro",
    "Right": "Derecha",
    "Undo": "Deshacer",
    "Redo": "Rehacer"
}
```

### Localization
If you need to localize the editor's aria-label or tooltip copy, you'll need to register the following translation keys in one of your app's lang files.

```html
// lang/es.json

{
    "Rich text editor": "Editor de texto enriquecido",
    "Formatting": "Formato",
    "Text": "Texto",
    "Heading 1": "Encabezado 1",
    "Heading 2": "Encabezado 2",
    "Heading 3": "Encabezado 3",
    "Styles": "Estilos",
    "Bold": "Negrita",
    "Italic": "Cursiva",
    "Underline": "Subrayado",
    "Strikethrough": "Tachado",
    "Subscript": "Subíndice",
    "Superscript": "Superíndice",
    "Highlight": "Resaltar",
    "Code": "Código",
    "Bullet list": "Lista con viñetas",
    "Ordered list": "Lista numerada",
    "Blockquote": "Cita",
    "Insert link": "Insertar enlace",
    "Unlink": "Quitar enlace",
    "Align": "Alinear",
    "Left": "Izquierda",
    "Center": "Centro",
    "Right": "Derecha",
    "Undo": "Deshacer",
    "Redo": "Rehacer"
}
```

### Extensions
Tiptap has a wide range of extensions that can be used to add custom functionality to the editor.

```html
<head>
    ...
    <script type="module">
        document.addEventListener('flux:editor', (e) => {
            ...
        })
    </script>
</head>
```

### Set up listener
Container for editor toolbar items. Can be used for custom toolbar layouts.

```html
<head>
    ...
    <script type="module">
        document.addEventListener('flux:editor', (e) => {
            ...
        })
    </script>
</head>
```

### Registering extensions
Container for editor toolbar items. Can be used for custom toolbar layouts.

```html
import Youtube from 'https://cdn.jsdelivr.net/npm/@tiptap/extension-youtube@2.11.7/+esm'

document.addEventListener('flux:editor', (e) => {
    e.detail.registerExtension(
        Youtube.configure({
            controls: false,
            nocookie: true,
        }),
    )
})
```

### Disabling extensions
Container for editor toolbar items. Can be used for custom toolbar layouts.

```html
document.addEventListener('flux:editor', (e) => {
    e.detail.disableExtension('underline')
})
```

### Accessing the instance
Container for editor toolbar items. Can be used for custom toolbar layouts.

```html
document.addEventListener('flux:editor', (e) => {
    e.detail.init(({ editor }) => {
        editor.on('create', () => {})

        editor.on('update', () => {})

        editor.on('selectionUpdate', () => {})

        editor.on('transaction', () => {})

        editor.on('focus', () => {})

        editor.on('blur', () => {})

        editor.on('destroy', () => {})

        editor.on('drop', () => {})

        editor.on('paste', () => {})

        editor.on('contentError', () => {})
    })
})
```


| Operation | Windows/Linux | Mac |
| --- | --- | --- |
| Apply paragraph style | Ctrl+Alt+0 | Cmd+Alt+0 |
| Apply heading level 1 | Ctrl+Alt+1 | Cmd+Alt+1 |
| Apply heading level 2 | Ctrl+Alt+2 | Cmd+Alt+2 |
| Apply heading level 3 | Ctrl+Alt+3 | Cmd+Alt+3 |
| Bold | Ctrl+B | Cmd+B |
| Italic | Ctrl+I | Cmd+I |
| Underline | Ctrl+U | Cmd+U |
| Strikethrough | Ctrl+Shift+X | Cmd+Shift+X |
| Bullet list | Ctrl+Shift+8 | Cmd+Shift+8 |
| Ordered list | Ctrl+Shift+7 | Cmd+Shift+7 |
| Blockquote | Ctrl+Shift+B | Cmd+Shift+B |
| Code | Ctrl+E | Cmd+E |
| Highlight | Ctrl+Shift+H | Cmd+Shift+H |
| Align left | Ctrl+Shift+L | Cmd+Shift+L |
| Align center | Ctrl+Shift+E | Cmd+Shift+E |
| Align right | Ctrl+Shift+R | Cmd+Shift+R |
| Paste without formatting | Ctrl+Shift+V | Cmd+Shift+V |
| Add a line break | Ctrl+Shift+Enter | Cmd+Shift+Enter |
| Undo | Ctrl+Z | Cmd+Z |
| Redo | Ctrl+Shift+Z | Cmd+Shift+Z |


| Markdown | Operation |
| --- | --- |
| # | Apply heading level 1 |
| ## | Apply heading level 2 |
| ### | Apply heading level 3 |
| ** | Bold |
| * | Italic |
| ~~ | Strikethrough |
| - | Bullet list |
| 1. | Ordered list |
| > | Blockquote |
| ` | Inline code |
| ``` | Code block |
| ```? | Code block (with class="language-?") |
| --- | Horizontal rule |


| Prop | Description |
| --- | --- |
| wire:model | Binds the editor to a Livewire property. See the wire:model documentation for more information. |
| value | Initial content for the editor. Used when not binding with wire:model. |
| label | Label text displayed above the editor. When provided, wraps the editor in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the editor. When provided alongside label, appears between the label and editor within the flux:field wrapper. See the field component. |
| description:trailing | The description provided will be displayed below the editor instead of above it. |
| badge | Badge text displayed at the end of the flux:label component when the label prop is provided. |
| placeholder | Placeholder text displayed when the editor is empty. |
| toolbar | Space-separated list of toolbar items to display. Use | for separator and ~ for spacer. By default, includes heading, bold, italic, strike, bullet, ordered, blockquote, link, and align tools. |
| disabled | Prevents user interaction with the editor. |
| invalid | Applies error styling to the editor. |


| Slot | Description |
| --- | --- |
| default | The editor content and toolbar components. If omitted, the standard toolbar and an empty content area will be used. |


| Attribute | Description |
| --- | --- |
| data-flux-editor | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| items | Space-separated list of toolbar items to display. Use | for separator and ~ for spacer. If not provided, displays the default toolbar. |


| Slot | Description |
| --- | --- |
| default | The toolbar items, separators, and spacers. Use this slot to create a completely custom toolbar. |


| Prop | Description |
| --- | --- |
| icon | Name of the icon to display in the button. |
| iconVariant | The variant of the icon to display. Options: mini, micro, outline. Default: mini (without slot) or micro (with slot). |
| tooltip | Text to display in a tooltip when hovering over the button. |
| disabled | Prevents interaction with the button. |


| Slot | Description |
| --- | --- |
| default | Content to display inside the button. If provided alongside an icon, the icon will be displayed before this content. |


| Slot | Description |
| --- | --- |
| default | The initial HTML content for the editor. This content will be processed and managed by the editor. |


| Component | Description |
| --- | --- |
| heading | Heading level selector. |
| bold | Bold text formatting. |
| italic | Italic text formatting. |
| strike | Strikethrough text formatting. |
| underline | Underline text formatting. |
| bullet | Bulleted list. |
| ordered | Numbered list. |
| blockquote | Block quote formatting. |
| code | Code block formatting. |
| link | Link insertion. |
| align | Text alignment options. |
| undo | Undo last action. |
| redo | Redo last action. |



---

## Field

Encapsulate input elements with labels, descriptions, and validation.

```html
<flux:field>
    <flux:label>Email</flux:label>

    <flux:input wire:model="email" type="email" />

    <flux:error name="email" />
</flux:field>
```

### Shorthand props
Because using the field component in its full form can be verbose and repetitive, all form controls in flux allow you pass a label and a description parameter directly. Under the hood, they will be wrapped in a field with an error component automatically.

```html
<flux:input wire:model="email" label="Email" type="email" />
```

### With trailing description
Position the field description directly below the input.

```html
<flux:field>
    <flux:label>Password</flux:label>

    <flux:input type="password" />

    <flux:error name="password" />

    <flux:description>Must be at least 8 characters long, include an uppercase letter, a number, and a special character.</flux:description>
</flux:field>

<!-- Alternative shorthand syntax... -->

<flux:input
    type="password"
    label="Password"
    description:trailing="Must be at least 8 characters long, include an uppercase letter, a number, and a special character."
/>
```

### With badge
Badges allow you to enhance a field with additional information such as being "required" or "optional" when it might not be expected.

```html
<flux:field>
    <flux:label badge="Required">Email</flux:label>

    <flux:input type="email" required />

    <flux:error name="email" />
</flux:field>

<flux:field>
    <flux:label badge="Optional">Phone number</flux:label>

    <flux:input type="phone" placeholder="(555) 555-5555" mask="(999) 999-9999"  />

    <flux:error name="phone" />
</flux:field>
```

### Split layout
Display multiple fields horizontally in the same row.

```html
<div class="grid grid-cols-2 gap-4">
    <flux:input label="First name" placeholder="River" />

    <flux:input label="Last name" placeholder="Porzio" />
</div>
```

### Fieldset
Group related fields using the fieldset and legend component.

```html
<flux:fieldset>
    <flux:legend>Shipping address</flux:legend>

    <div class="space-y-6">
        <flux:input label="Street address line 1" placeholder="123 Main St" class="max-w-sm" />
        <flux:input label="Street address line 2" placeholder="Apartment, studio, or floor" class="max-w-sm" />

        <div class="grid grid-cols-2 gap-x-4 gap-y-6">
            <flux:input label="City" placeholder="San Francisco" />
            <flux:input label="State / Province" placeholder="CA" />
            <flux:input label="Postal / Zip code" placeholder="12345" />
            <flux:select label="Country">
                <option selected>United States</option>
                <!-- ... -->
            </flux:select>
        </div>
    </div>
</flux:fieldset>
```


| Prop | Description |
| --- | --- |
| variant | Visual style variant. Options: block, inline. Default: block. |


| Slot | Description |
| --- | --- |
| default | The form control elements (input, select, etc.) and their associated labels, descriptions, and error messages. |


| Attribute | Description |
| --- | --- |
| data-flux-field | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| badge | Optional text to display as a badge (e.g., "Required", "Optional"). |


| Slot | Description |
| --- | --- |
| default | The label text content. |


| Slot | Description |
| --- | --- |
| default | The descriptive text content. |


| Prop | Description |
| --- | --- |
| name | The name of the field to display validation errors for. |
| message | Custom error message content (optional). |


| Slot | Description |
| --- | --- |
| default | Custom error message content (optional). |


| Prop | Description |
| --- | --- |
| legend | The fieldset's heading text. |
| description | Optional description text for the fieldset. |


| Slot | Description |
| --- | --- |
| default | The grouped form fields and their associated legend. |


| Slot | Description |
| --- | --- |
| default | The fieldset's heading text. |


| Slot | Description |
| --- | --- |
| default | The descriptive text content. |



---

## Heading

A consistent heading component for your application.

```html
<flux:heading>User profile</flux:heading>
<flux:text class="mt-2">This information will be displayed publicly.</flux:text>
```

### Sizes
Flux offers three different heading sizes that should cover most use cases in your app.

```html
<flux:heading>Default</flux:heading>
<flux:heading size="lg">Large</flux:heading>
<flux:heading size="xl">Extra large</flux:heading>
```

### Heading level
Control the heading level: h1, h2, h3, that will be used for the heading element. Without a level prop, the heading will default to a div.

```html
<flux:heading level="3">User profile</flux:heading>
<flux:text class="mt-2">This information will be displayed publicly.</flux:text>
```


| Prop | Description |
| --- | --- |
| size | Size of the heading. Options: base, lg, xl. Default: base. |
| level | HTML heading level. Options: 1, 2, 3, 4. Default: renders as a div if not specified. |
| accent | If true, applies accent color styling to the heading. |


| Prop | Description |
| --- | --- |
| size | Size of the text. Options: sm, base, lg, xl. Default: base. |



---

## Icon

Flux uses the excellent Heroicons project for its icon collection. Heroicons is a set of beautiful and functional icons crafted by the fine folks at Tailwind Labs

```html
<flux:icon.bolt />
```

### Variants
There are four variants for each icon: outline (default), solid, mini, and micro.

```html
<flux:icon.bolt />                  <!-- 24px, outline -->
<flux:icon.bolt variant="solid" />  <!-- 24px, filled -->
<flux:icon.bolt variant="mini" />   <!-- 20px, filled -->
<flux:icon.bolt variant="micro" />  <!-- 16px, filled -->
```

### Sizes
Control the size (height/width) of an icon using the size-* Tailwind utility.

```html
<flux:icon.bolt class="size-12" />
<flux:icon.bolt class="size-10" />
<flux:icon.bolt class="size-8" />
```

### Color
You can customize the color of an icon using Tailwind's text color utilities

```html
<flux:icon.bolt variant="solid" class="text-amber-500 dark:text-amber-300" />
```

### Loading spinner
Flux has a special loading spinner icon that isn't part of the Heroicons collection. You can use this special icon anywhere you would normally use a standard icon.

```html
<flux:icon.loading />
```

### Lucide icons
We love Heroicons, but we acknowledge that it's a fairly limited icon set. If you need more icons, we recommend using Lucide instead.

```html
php artisan flux:icon
```

### Custom icons
For full control over your icons, you can create your own Blade files in the resources/views/flux/icon directory in your project.

```html
- resources
    - views
        - flux
            - icon
                - wink.blade.php
```


| Prop | Description |
| --- | --- |
| variant | Visual style of the icon. Options: outline (default), solid, mini, micro. |


| Class | Description |
| --- | --- |
| size-* | Control the size of the icon using Tailwind's size utilities (e.g., size-8, size-12). |
| text-* | Control the color of the icon using Tailwind's text color utilities (e.g., text-blue-500). |


| Attribute | Description |
| --- | --- |
| data-flux-icon | Applied to the root SVG element for styling and identification. |


| Size | Description |
| --- | --- |
| outline | 24x24 pixels (default) |
| solid | 24x24 pixels |
| mini | 20x20 pixels |
| micro | 16x16 pixels |



---

## Input

Capture user data with various forms of text input.

```html
<flux:field>
    <flux:label>Username</flux:label>

    <flux:description>This will be publicly displayed.</flux:description>

    <flux:input />

    <flux:error name="username" />
</flux:field>
```

### Shorthand
Because using the field component in its full form can be verbose and repetitive, all form controls in flux allow you pass a label and a description parameter directly. Under the hood, they will be wrapped in a field with an error component automatically.

```html
<flux:input label="Username" description="This will be publicly displayed." />
```

### Class targeting
Unlike other form components, Flux's input component is composed of two underlying elements: an input element and a wrapper div. The wrapper div is there to add padding where icons should go.

```html
<flux:input class="max-w-xs" class:input="font-mono" />
```

### Types
Use the browser's various input types for different situations: text, email, password, etc.

```html
<flux:input type="email" label="Email" />
<flux:input type="password" label="Password" />
<flux:input type="date" max="2999-12-31" label="Date" />
```

### File
Flux provides a special input component for file uploads. It's a simple wrapper around the native input[type="file"] element.

```html
<flux:input type="file" wire:model="logo" label="Logo"/>
<flux:input type="file" wire:model="attachments" label="Attachments" multiple />
```

### Smaller
Use the size prop to make the input's height more compact.

```html
<flux:input size="sm" placeholder="Filter by..." />
```

### Disabled
Prevent users from interacting with an input by disabling it.

```html
<flux:input disabled label="Email" />
```

### Readonly
Useful for locking an input during a form submission.

```html
<flux:input readonly variant="filled" />
```

### Invalid
Signal to users that the contents of an input are invalid.

```html
<flux:input invalid />
```

### Input masking
Restrict the formatting of text content for unique cases by using Alpine's mask plugin

```html
<flux:input mask="(999) 999-9999" value="7161234567" />
```

### Icons
Append or prepend an icon to the inside of a form input.

```html
<flux:input icon="magnifying-glass" placeholder="Search orders" />

<flux:input icon:trailing="credit-card" placeholder="4444-4444-4444-4444" />

<flux:input icon:trailing="loading" placeholder="Search transactions" />
```

### Icon buttons
Append a button to the inside of an input to provide associated functionality.

```html
<flux:input placeholder="Search orders">
    <x-slot name="iconTrailing">
        <flux:button size="sm" variant="subtle" icon="x-mark" class="-mr-1" />
    </x-slot>
</flux:input>

<flux:input type="password" value="password">
    <x-slot name="iconTrailing">
        <flux:button size="sm" variant="subtle" icon="eye" class="-mr-1" />
    </x-slot>
</flux:input>
```

### Clearable, copyable, and viewable inputs
Flux provides three special input properties to configure common input button behaviors. clearable for clearing contents, copyable for copying contents, and viewable for toggling password visibility.

```html
<flux:input placeholder="Search orders" clearable />
<flux:input type="password" value="password" viewable />
<flux:input icon="key" value="FLUX-1234-5678-ABCD-EFGH" readonly copyable />
```

### Keyboard hint
Hint to users what keyboard shortcuts they can use with this input.

```html
<flux:input kbd="⌘K" icon="magnifying-glass" placeholder="Search..."/>
```

### As a button
To render an input using a button element, pass "button" into the as prop.

```html
<flux:input as="button" placeholder="Search..." icon="magnifying-glass" kbd="⌘K" />
```

### With buttons
Attach buttons to the beginning or end of an input element.

```html
<flux:input.group>
    <flux:input placeholder="Post title" />

    <flux:button icon="plus">New post</flux:button>
</flux:input.group>

<flux:input.group>
    <flux:select class="max-w-fit">
        <flux:select.option selected>USD</flux:select.option>
        <!-- ... -->
    </flux:select>

    <flux:input placeholder="$99.99" />
</flux:input.group>
```

### Text prefixes and suffixes
Append text inside a form input.

```html
<flux:input.group>
    <flux:input.group.prefix>https://</flux:input.group.prefix>

    <flux:input placeholder="example.com" />
</flux:input.group>

<flux:input.group>
    <flux:input placeholder="chunky-spaceship" />

    <flux:input.group.suffix>.brand.com</flux:input.group.suffix>
</flux:input.group>
```

### Input group labels
If you want to use an input group in a form field with a label, you will need to wrap the input group in a field component.

```html
<flux:field>
    <flux:label>Website</flux:label>

    <flux:input.group>
        <flux:input.group.prefix>https://</flux:input.group.prefix>

        <flux:input wire:model="website" placeholder="example.com" />
    </flux:input.group>

    <flux:error name="website" />
</flux:field>
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the input to a Livewire property. See the wire:model documentation for more information. |
| label | Label text displayed above the input. When provided, wraps the input in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed above the input. When provided alongside label, appears between the label and input within the flux:field wrapper. See the field component. |
| description:trailing | Help text displayed below the input. When provided alongside label, appears between the label and input within the flux:field wrapper. See the field component. |
| placeholder | Placeholder text displayed when the input is empty. |
| size | Size of the input. Options: sm, xs. |
| variant | Visual style variant. Options: filled. Default: outline. |
| disabled | Prevents user interaction with the input. |
| readonly | Makes the input read-only. |
| invalid | Applies error styling to the input. |
| multiple | For file inputs, allows selecting multiple files. |
| mask | Input mask pattern using Alpine's mask plugin. Example: 99/99/9999. |
| icon | Name of the icon displayed at the start of the input. |
| icon:trailing | Name of the icon displayed at the end of the input. |
| kbd | Keyboard shortcut hint displayed at the end of the input. |
| clearable | If true, displays a clear button when the input has content. |
| copyable | If true, displays a copy button to copy the input's content. |
| viewable | For password inputs, displays a toggle to show/hide the password. |
| as | Render the input as a different element. Options: button. Default: input. |
| class:input | CSS classes applied directly to the input element instead of the wrapper. |


| Slot | Description |
| --- | --- |
| icon | Custom content displayed at the start of the input (e.g., icons). |
| icon:leading | Custom content displayed at the start of the input (e.g., icons). |
| icon:trailing | Custom content displayed at the end of the input (e.g., buttons). |


| Attribute | Description |
| --- | --- |
| data-flux-input | Applied to the root element for styling and identification. |


| Slot | Description |
| --- | --- |
| default | The input group content, typically containing an input and prefix/suffix elements. |


| Slot | Description |
| --- | --- |
| default | Content displayed before the input (e.g., icons, text, buttons). |


| Slot | Description |
| --- | --- |
| default | Content displayed after the input (e.g., icons, text, buttons). |



---

## Modal

Display content in a layer above the main page. Ideal for confirmations, alerts, and forms.

```html
<flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>

        <flux:input label="Name" placeholder="Your name" />

        <flux:input label="Date of birth" type="date" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>
```

### Unique modal names
If you are placing modals inside a loop, ensure that you are dynamically generating unique modal names. Otherwise, one modal trigger, will trigger all modals of that name on the page causing unexpected behavior.

```html
@foreach ($users as $user)
    <flux:modal :name="'edit-profile-'.$user->id">
        ...
    </flux:modal>
@endforeach
```

### Livewire methods
In addition to triggering modals in your Blade templates, you can also control them directly from Livewire.

```html
<flux:modal name="confirm">
    <!-- ... -->
</flux:modal>
```

### JavaScript methods
You can also control modals from Alpine directly using Flux's magic methods:

```html
<button x-on:click="$flux.modal('confirm').show()">
    Open modal
</button>

<button x-on:click="$flux.modal('confirm').close()">
    Close modal
</button>

<button x-on:click="$flux.modals().close()">
    Close all modals
</button>
```

### Data binding
If you prefer, you can bind a Livewire property directly to a modal to control its states from your Livewire component.

```html
<flux:modal wire:model.self="showConfirmModal">
    <!-- ... -->
</flux:modal>
```

### Close events
If you need to perform some logic after a modal closes, you can register a close listener like so:

```html
<flux:modal @close="someLivewireAction">
    <!-- ... -->
</flux:modal>
```

### Cancel events
If you need to perform some logic after a modal is cancelled, you can register a cancel listener like so:

```html
<flux:modal @cancel="someLivewireAction">
    <!-- ... -->
</flux:modal>
```

### Disable click outside
By default, clicking outside the modal will close it. If you want to disable this behavior, you can use the :dismissible="false" prop.

```html
<flux:modal :dismissible="false">
    <!-- ... -->
</flux:modal>
```

### Confirmation
Prompt a user for confirmation before performing a dangerous action.

```html
<flux:modal.trigger name="delete-profile">
    <flux:button variant="danger">Delete</flux:button>
</flux:modal.trigger>

<flux:modal name="delete-profile" class="min-w-[22rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Delete project?</flux:heading>

            <flux:text class="mt-2">
                <p>You're about to delete this project.</p>
                <p>This action cannot be reversed.</p>
            </flux:text>
        </div>

        <div class="flex gap-2">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>

            <flux:button type="submit" variant="danger">Delete project</flux:button>
        </div>
    </div>
</flux:modal>
```

### Flyout
Use the "flyout" variant for a more anchored and long-form dialog.

```html
<flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" variant="flyout">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>

        <flux:input label="Name" placeholder="Your name" />

        <flux:input label="Date of birth" type="date" />

        <div class="flex">
            <flux:spacer />

            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>
```

### Flyout positioning
PHP method for controlling modals from Livewire components.

```html
<flux:modal variant="flyout" position="left">
    <!-- ... -->
</flux:modal>
```


| Prop | Description |
| --- | --- |
| name | Unique identifier for the modal. Required when using triggers. |
| variant | Visual style of the modal. Options: default, flyout, bare. |
| position | For flyout modals, the direction they open from. Options: right (default), left, bottom. |
| dismissible | If false, prevents closing the modal by clicking outside. Default: true. |
| wire:model | Optional Livewire property to bind the modal's open state to. |


| Event | Description |
| --- | --- |
| close | Triggered when the modal is closed by any means. |
| cancel | Triggered when the modal is closed by clicking outside or pressing escape. |


| Slot | Description |
| --- | --- |
| default | The modal content. |


| Class | Description |
| --- | --- |
| w-* | Common use: md:w-96 for width. |


| Prop | Description |
| --- | --- |
| name | Name of the modal to trigger. Must match the modal's name. |
| shortcut | Keyboard shortcut to open the modal (e.g., cmd.k). |


| Slot | Description |
| --- | --- |
| default | The trigger element (e.g., button). |


| Slot | Description |
| --- | --- |
| default | The close trigger element (e.g., button). |


| Parameter | Description |
| --- | --- |
| default|name | Name of the modal to control. |


| Method | Description |
| --- | --- |
| close() | Closes the modal. |


| Method | Description |
| --- | --- |
| close() | Closes all modals on the page. |


| Parameter | Description |
| --- | --- |
| default|name | Name of the modal to control. |


| Method | Description |
| --- | --- |
| show() | Shows the modal. |
| close() | Closes the modal. |



---

## Navbar

Arrange navigation links vertically or horizontally.

```html
<flux:navbar>
    <flux:navbar.item href="#">Home</flux:navbar.item>
    <flux:navbar.item href="#">Features</flux:navbar.item>
    <flux:navbar.item href="#">Pricing</flux:navbar.item>
    <flux:navbar.item href="#">About</flux:navbar.item>
</flux:navbar>
```

### Detecting the current page
Navbars and navlists will try to automatically detect and mark the current page based on the href attribute passed in. However, if you need full control, you can pass the current prop to the item directly.

```html
<flux:navbar.item href="/" current>Home</flux:navbar.item>
<flux:navbar.item href="/" :current="false">Home</flux:navbar.item>
<flux:navbar.item href="/" :current="request()->is('/')">Home</flux:navbar.item>
```

### With icons
Add a leading icons for visual context.

```html
<flux:navbar>
    <flux:navbar.item href="#" icon="home">Home</flux:navbar.item>
    <flux:navbar.item href="#" icon="puzzle-piece">Features</flux:navbar.item>
    <flux:navbar.item href="#" icon="currency-dollar">Pricing</flux:navbar.item>
    <flux:navbar.item href="#" icon="user">About</flux:navbar.item>
</flux:navbar>
```

### With badges
Add a trailing badge to a navbar item using the badge prop.

```html
<flux:navbar>
    <flux:navbar.item href="#">Home</flux:navbar.item>
    <flux:navbar.item href="#" badge="12">Inbox</flux:navbar.item>
    <flux:navbar.item href="#">Contacts</flux:navbar.item>
    <flux:navbar.item href="#" badge="Pro" badge-color="lime">Calendar</flux:navbar.item>
</flux:navbar>
```

### Dropdown navigation
Condense multiple navigation items into a single dropdown menu to save on space and group related items.

```html
<flux:navbar>
    <flux:navbar.item href="#">Dashboard</flux:navbar.item>
    <flux:navbar.item href="#">Transactions</flux:navbar.item>

    <flux:dropdown>
        <flux:navbar.item icon:trailing="chevron-down">Account</flux:navbar.item>

        <flux:navmenu>
            <flux:navmenu.item href="#">Profile</flux:navmenu.item>
            <flux:navmenu.item href="#">Settings</flux:navmenu.item>
            <flux:navmenu.item href="#">Billing</flux:navmenu.item>
        </flux:navmenu>
    </flux:dropdown>
</flux:navbar>
```

### Navlist (sidebar)
Arrange your navbar vertically using the navlist component.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Home</flux:navlist.item>
    <flux:navlist.item href="#" icon="puzzle-piece">Features</flux:navlist.item>
    <flux:navlist.item href="#" icon="currency-dollar">Pricing</flux:navlist.item>
    <flux:navlist.item href="#" icon="user">About</flux:navlist.item>
</flux:navlist>
```

### Navlist group
Group related navigation items.

```html
<flux:navlist>
    <flux:navlist.group heading="Account" class="mt-4">
        <flux:navlist.item href="#">Profile</flux:navlist.item>
        <flux:navlist.item href="#">Settings</flux:navlist.item>
        <flux:navlist.item href="#">Billing</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

### Collapsible groups
Group related navigation items into collapsible sections using the expandable prop.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Dashboard</flux:navlist.item>
    <flux:navlist.item href="#" icon="list-bullet">Transactions</flux:navlist.item>

    <flux:navlist.group heading="Account" expandable>
        <flux:navlist.item href="#">Profile</flux:navlist.item>
        <flux:navlist.item href="#">Settings</flux:navlist.item>
        <flux:navlist.item href="#">Billing</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

### Navlist badges
Show additional information related to a navlist item using the badge prop.

```html
<flux:navlist class="w-64">
    <flux:navlist.item href="#" icon="home">Home</flux:navlist.item>
    <flux:navlist.item href="#" icon="envelope" badge="12">Inbox</flux:navlist.item>
    <flux:navlist.item href="#" icon="user-group">Contacts</flux:navlist.item>
    <flux:navlist.item href="#" icon="calendar-days" badge="Pro" badge-color="lime">Calendar</flux:navlist.item>
</flux:navlist>
```


| Slot | Description |
| --- | --- |
| default | The navigation items. |


| Attribute | Description |
| --- | --- |
| data-flux-navbar | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| href | URL the item links to. |
| current | If true, applies active styling to the item. Auto-detected based on current URL if not specified. |
| icon | Name of the icon to display at the start of the item. |
| icon:trailing | Name of the icon to display at the end of the item. |


| Attribute | Description |
| --- | --- |
| data-current | Applied when the item is active/current. |


| Slot | Description |
| --- | --- |
| default | The navigation items and groups. |


| Attribute | Description |
| --- | --- |
| data-flux-navlist | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| href | URL the item links to. |
| current | If true, applies active styling to the item. Auto-detected based on current URL if not specified. |
| icon | Name of the icon to display at the start of the item. |


| Attribute | Description |
| --- | --- |
| data-current | Applied when the item is active/current. |


| Prop | Description |
| --- | --- |
| heading | Text displayed as the group heading. |
| expandable | If true, makes the group collapsible. |
| expanded | If true, expands the group by default when expandable. |


| Slot | Description |
| --- | --- |
| default | The group's navigation items. |



---

## Pagination

Display a series of buttons to navigate through a list of items.

```html
<!-- $orders = Order::paginate() -->
<flux:pagination :paginator="$orders" />
```

### Simple paginator
Use the simple paginator when working with large datasets where counting the total number of results would be expensive. The simple paginator provides "Previous" and "Next" buttons without displaying the total number of pages or records.

```html
<!-- $orders = Order::simplePaginate() -->
<flux:pagination :paginator="$orders" />
```

### Large result set
When working with large result sets, the pagination component automatically adapts to show a reasonable number of page links. It shows the first and last pages, along with a window of pages around the current page, and adds ellipses for any gaps to ensure efficient navigation through numerous pages.

```html
<!-- $orders = Order::paginate(5) -->
<flux:pagination :paginator="$orders" />
```


| Prop | Description |
| --- | --- |
| paginator | The paginator instance to display. |



---

## Profile

Display a user's profile with an avatar and optional name in a compact, interactive component.

```html
<flux:profile avatar="https://unavatar.io/x/calebporzio" />
```

### With name
Display a user's name next to their avatar.

```html
<flux:profile name="Caleb Porzio" avatar="https://unavatar.io/x/calebporzio" />
```

### Without chevron
Hide the chevron icon by setting the :chevron prop to false.

```html
<flux:profile :chevron="false" avatar="https://unavatar.io/x/calebporzio" />
```

### Circle avatar
Use the circle prop to display a circular avatar.

```html
<flux:profile circle :chevron="false" avatar="https://unavatar.io/x/calebporzio" />

<flux:profile circle name="Caleb Porzio" avatar="https://unavatar.io/x/calebporzio" />
```

### Avatar with initials
When no avatar image is provided, initials will be automatically generated from the name or they can be specified directly.

```html
<!-- Automatically generates initials from name -->
<flux:profile name="Caleb Porzio" />

<!-- Specify color... -->
<flux:profile name="Caleb Porzio" avatar:color="cyan" />

<!-- Manually specify initials... -->
<flux:profile initials="CP" />

<!-- Provide name only for avatar initial generation... -->
<flux:profile avatar:name="Caleb Porzio" />
```

### Custom trailing icon
Replace the default chevron with a custom icon using the icon:trailing prop.

```html
<flux:profile
    icon:trailing="chevron-up-down"
    avatar="https://unavatar.io/x/calebporzio"
    name="Caleb Porzio"
/>
```


| Prop | Description |
| --- | --- |
| name | User's name to display next to the avatar. |
| avatar | URL to the image to display as avatar, or can pass content via avatar named slot. |
| avatar:name | Name to use for avatar initial generation. |
| avatar:color | Color to use for the avatar. (See Avatar color documentation for available options.) |
| circle | Whether to display a circular avatar. Default: false. |
| initials | Custom initials to display when no avatar image is provided. Automatically generated from name if not provided. |
| chevron | Whether to display a chevron icon (dropdown indicator). Default: true. |
| icon:trailing | Custom icon to display instead of the chevron. Accepts any icon name. |
| icon:variant | Icon variant to use for the trailing icon. Options: micro (default), outline. |


| Slot | Description |
| --- | --- |
| avatar | Custom content for the avatar section, typically containing a flux:avatar component. |



---

## Radio

Select one option from a set of mutually exclusive choices. Perfect for single-choice questions and settings.

```html
<flux:radio.group wire:model="payment" label="Select your payment method">
    <flux:radio value="cc" label="Credit Card" checked />
    <flux:radio value="paypal" label="Paypal" />
    <flux:radio value="ach" label="Bank transfer" />
</flux:radio.group>
```

### With descriptions
Align descriptions for each radio directly below its label.

```html
<flux:radio.group label="Role">
    <flux:radio
        name="role"
        value="administrator"
        label="Administrator"
        description="Administrator users can perform any action."
        checked
    />
    <flux:radio
        name="role"
        value="editor"
        label="Editor"
        description="Editor users have the ability to read, create, and update."
    />
    <flux:radio
        name="role"
        value="viewer"
        label="Viewer"
        description="Viewer users only have the ability to read. Create, and update are restricted."
    />
</flux:radio.group>
```

### Within fieldset
Group radio inputs inside a fieldset and provide more context with descriptions for each radio option.

```html
<flux:fieldset>
    <flux:legend>Role</flux:legend>

    <flux:radio.group>
        <flux:radio
            value="administrator"
            label="Administrator"
            description="Administrator users can perform any action."
            checked
        />
        <flux:radio
            value="editor"
            label="Editor"
            description="Editor users have the ability to read, create, and update."
        />
        <flux:radio
            value="viewer"
            label="Viewer"
            description="Viewer users only have the ability to read. Create, and update are restricted."
        />
    </flux:radio.group>
</flux:fieldset>
```

### Segmented
A more compact alternative to standard radio buttons.

```html
<flux:radio.group wire:model="role" label="Role" variant="segmented">
    <flux:radio label="Admin" />
    <flux:radio label="Editor" />
    <flux:radio label="Viewer" />
</flux:radio.group>
```

### Segmented with icons
Combine segmented radio buttons with icon prefixes.

```html
<flux:radio.group wire:model="role" variant="segmented">
    <flux:radio label="Admin" icon="wrench" />
    <flux:radio label="Editor" icon="pencil-square" />
    <flux:radio label="Viewer" icon="eye" />
</flux:radio.group>
```

### Radio cards
A bordered alternative to standard radio buttons.

```html
<flux:radio.group wire:model="shipping" label="Shipping" variant="cards" class="max-sm:flex-col">
    <flux:radio value="standard" label="Standard" description="4-10 business days" checked />
    <flux:radio value="fast" label="Fast" description="2-5 business days" />
    <flux:radio value="next-day" label="Next day" description="1 business day" />
</flux:radio.group>
```

### Vertical cards
You can arrange a set of radio cards vertically by simply adding the flex-col class to the group container.

```html
<flux:radio.group label="Shipping" variant="cards" class="flex-col">
    <flux:radio value="standard" label="Standard" description="4-10 business days" />
    <flux:radio value="fast" label="Fast" description="2-5 business days" />
    <flux:radio value="next-day" label="Next day" description="1 business day" />
</flux:radio.group>
```

### Cards with icons
You can arrange a set of radio cards vertically by simply adding the flex-col class to the group container.

```html
<flux:radio.group label="Shipping" variant="cards" class="max-sm:flex-col">
    <flux:radio value="standard" icon="truck" label="Standard" description="4-10 business days" />
    <flux:radio value="fast" icon="cube" label="Fast" description="2-5 business days" />
    <flux:radio value="next-day" icon="clock" label="Next day" description="1 business day" />
</flux:radio.group>
```

### Cards without indicators
For a cleaner look, you can remove the radio indicator using :indicator="false".

```html
<flux:radio.group label="Shipping" variant="cards" :indicator="false" class="max-sm:flex-col">
    <flux:radio value="standard" icon="truck" label="Standard" description="4-10 business days" />
    <flux:radio value="fast" icon="cube" label="Fast" description="2-5 business days" />
    <flux:radio value="next-day" icon="clock" label="Next day" description="1 business day" />
</flux:radio.group>
```

### Custom card content
You can compose your own custom cards through the flux:radio component slot.

```html
<flux:radio.group label="Shipping" variant="cards" class="max-sm:flex-col">
    <flux:radio value="standard" checked>
        <flux:radio.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Standard</flux:heading>
            <flux:text size="sm" class="mt-2">4-10 business days</flux:text>
        </div>
    </flux:radio>

    <flux:radio value="fast">
        <flux:radio.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Fast</flux:heading>
            <flux:text size="sm" class="mt-2">2-5 business days</flux:text>
        </div>
    </flux:radio>

    <flux:radio value="next-day">
        <flux:radio.indicator />

        <div class="flex-1">
            <flux:heading class="leading-4">Next day</flux:heading>
            <flux:text size="sm" class="mt-2">1 business day</flux:text>
        </div>
    </flux:radio>
</flux:radio.group>
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the radio group selection to a Livewire property. See the wire:model documentation for more information. |
| label | Label text displayed above the radio group. When provided, wraps the radio group in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the radio group. When provided alongside label, appears between the label and radio group within the flux:field wrapper. See the field component. |
| variant | Visual style of the group. Options: default, segmented, cards. |
| invalid | Applies error styling to the radio group. |


| Slot | Description |
| --- | --- |
| default | The radio buttons to be grouped together. |


| Attribute | Description |
| --- | --- |
| data-flux-radio-group | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| label | Label text displayed above the radio button. When provided, wraps the radio button in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the radio button. When provided alongside label, appears between the label and radio button within the flux:field wrapper. See the field component. |
| value | Value associated with the radio button when used in a group. |
| checked | If true, the radio button is selected by default. |
| disabled | Prevents user interaction with the radio button. |
| icon | Name of the icon to display (for segmented variant). |


| Slot | Description |
| --- | --- |
| default | Custom content for card variant. |


| Attribute | Description |
| --- | --- |
| data-flux-radio | Applied to the root element for styling and identification. |
| data-checked | Applied when the radio button is selected. |



---

## Select

Choose a single option from a dropdown list.

```html
<flux:select wire:model="industry" placeholder="Choose industry...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### Small
A smaller select element for more compact layouts.

```html
<flux:select size="sm" placeholder="Choose industry...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### Custom select
An alternative to the browser's native select element. Typically used when you need custom option styling like icons, images, and other treatments.

```html
<flux:select variant="listbox" placeholder="Choose industry...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### The button slot
The searchable select variant makes navigating large option lists easier for your users.

```html
<flux:select variant="listbox">
    <x-slot name="button">
        <flux:select.button class="rounded-full!" placeholder="Choose industry..." :invalid="$errors->has('...')" />
    </x-slot>

    <flux:select.option>Photography</flux:select.option>
    ...
</flux:select>
```

### Clearable
The searchable select variant makes navigating large option lists easier for your users.

```html
<flux:select variant="listbox" clearable>
    ...
</flux:select>
```

### Options with images/icons
The searchable select variant makes navigating large option lists easier for your users.

```html
<flux:select variant="listbox" placeholder="Select role...">
    <flux:select.option>
        <div class="flex items-center gap-2">
            <flux:icon.shield-check variant="mini" class="text-zinc-400" /> Owner
        </div>
    </flux:select.option>

    <flux:select.option>
        <div class="flex items-center gap-2">
            <flux:icon.key variant="mini" class="text-zinc-400" /> Administrator
        </div>
    </flux:select.option>

    <flux:select.option>
        <div class="flex items-center gap-2">
            <flux:icon.user variant="mini" class="text-zinc-400" /> Member
        </div>
    </flux:select.option>

    <flux:select.option>
        <div class="flex items-center gap-2">
            <flux:icon.eye variant="mini" class="text-zinc-400" /> Viewer
        </div>
    </flux:select.option>
</flux:select>
```

### Searchable select
The searchable select variant makes navigating large option lists easier for your users.

```html
<flux:select variant="listbox" searchable placeholder="Choose industries...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### The search slot
Allow your users to select multiple options from a list of options.

```html
<flux:select variant="listbox" searchable>
    <x-slot name="search">
        <flux:select.search class="px-4" placeholder="Search industries..." />
    </x-slot>

    ...
</flux:select>
```

### Multiple select
Allow your users to select multiple options from a list of options.

```html
<flux:select variant="listbox" multiple placeholder="Choose industries...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### Selected suffix
A versatile combobox that can be used for anything from basic autocomplete to complex multi-selects.

```html
<flux:select variant="listbox" selected-suffix="industries selected" multiple>
    ...
</flux:select>
```

### Checkbox indicator
A versatile combobox that can be used for anything from basic autocomplete to complex multi-selects.

```html
<flux:select variant="listbox" indicator="checkbox" multiple>
    ...
</flux:select>
```

### Clearing search
A versatile combobox that can be used for anything from basic autocomplete to complex multi-selects.

```html
<flux:select variant="listbox" searchable multiple clear="close">
    ...
</flux:select>
```

### Combobox
A versatile combobox that can be used for anything from basic autocomplete to complex multi-selects.

```html
<flux:select variant="combobox" placeholder="Choose industry...">
    <flux:select.option>Photography</flux:select.option>
    <flux:select.option>Design services</flux:select.option>
    <flux:select.option>Web development</flux:select.option>
    <flux:select.option>Accounting</flux:select.option>
    <flux:select.option>Legal services</flux:select.option>
    <flux:select.option>Consulting</flux:select.option>
    <flux:select.option>Other</flux:select.option>
</flux:select>
```

### The input slot
If you want to dynamically generate options on the server, you can use the :filter="false" prop to disable client-side filtering.

```html
<flux:select variant="combobox">
    <x-slot name="input">
        <flux:select.input x-model="search" :invalid="$errors->has('...')" />
    </x-slot>

    ...
</flux:select>
```

### Dynamic options
If you want to dynamically generate options on the server, you can use the :filter="false" prop to disable client-side filtering.

```html
<flux:select wire:model="userId" variant="combobox" :filter="false">
    <x-slot name="input">
        <flux:select.input wire:model.live="search" />
    </x-slot>

    @foreach ($this->users as $user)
        <flux:select.option value="{{ $user->id }}" wire:key="{{ $user->id }}">
            {{ $user->name }}
        </flux:select.option>
    @endforeach
</flux:select>

<!--
public $search = '';

public $userId = null;

#[\Livewire\Attributes\Computed]
public function users() {
    return \App\Models\User::query()
        ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%'))
        ->limit(20)
        ->get();
}
-->
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the select to a Livewire property. See the wire:model documentation for more information. |
| placeholder | Text displayed when no option is selected. |
| label | Label text displayed above the select. When provided, wraps the select in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the select. When provided alongside label, appears between the label and select within the flux:field wrapper. See the field component. |
| description:trailing | The description provided will be displayed below the select instead of above it. |
| badge | Badge text displayed at the end of the flux:label component when the label prop is provided. |
| size | Size of the select. Options: sm, xs. |
| variant | Visual style of the select. Options: default (native select), listbox, combobox. |
| multiple | Allows selecting multiple options (listbox and combobox variants only). |
| filter | If false, disables client-side filtering. |
| searchable | Adds a search input to filter options (listbox and combobox variants only). |
| clearable | Displays a clear button when an option is selected (listbox and combobox variants only). |
| selected-suffix | Text appended to the number of selected options in multiple mode (listbox variant only). |
| clear | When to clear the search input. Options: select (default), close (listbox and combobox variants only). |
| disabled | Prevents user interaction with the select. |
| invalid | Applies error styling to the select. |


| Slot | Description |
| --- | --- |
| default | The select options. |
| trigger | Custom trigger content. Typically the select.button or select.input component (listbox and combobox variants only). |


| Attribute | Description |
| --- | --- |
| data-flux-select | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| value | Value associated with the option. |
| disabled | Prevents selecting the option. |


| Slot | Description |
| --- | --- |
| default | The option content (can include icons, images, etc. in listbox variant). |


| Prop | Description |
| --- | --- |
| placeholder | Text displayed when no option is selected. |
| invalid | Applies error styling to the button. |
| size | Size of the button. Options: sm, xs. |
| disabled | Prevents selecting the option. |
| clearable | Displays a clear button when an option is selected. |


| Prop | Description |
| --- | --- |
| placeholder | Text displayed when  no option is selected. |
| invalid | Applies error styling to the input. |
| size | Size of the input. Options: sm, xs. |


| Prop | Description |
| --- | --- |
| placeholder | Placeholder text displayed when the input is empty. |
| icon | Name of the icon displayed at the start of the input. |
| clearable | Displays a clear button when the input has content. Default: true. |



---

## Separator

Visually divide sections of content or groups of items.

```html
<flux:separator />
```

### With text
Add text to the separator for a more descriptive element.

```html
<flux:separator text="or" />
```

### Vertical
Seperate contents with a vertical seperator when horizontally stacked.

```html
<flux:separator vertical />
```

### Limited height
You can limit the height of the vertical separator by adding vertical margin.

```html
<flux:separator vertical class="my-2" />
```

### Subtle
Flux offers a subtle variant for a separator that blends into the background.

```html
<flux:separator vertical variant="subtle" />
```


| Prop | Description |
| --- | --- |
| vertical | Displays a vertical separator. Default is horizontal. |
| variant | Visual style variant. Options: subtle. Default: standard separator. |
| text | Optional text to display in the center of the separator. |
| orientation | Alternative to vertical prop. Options: horizontal, vertical. Default: horizontal. |


| Class | Description |
| --- | --- |
| my-* | Commonly used to shorten vertical separators. |


| Attribute | Description |
| --- | --- |
| data-flux-separator | Applied to the root element for styling and identification. |



---

## Switch

Toggle a setting on or off. Suitable for binary options like enabling or disabling features.

```html
<flux:field variant="inline">
    <flux:label>Enable notifications</flux:label>

    <flux:switch wire:model.live="notifications" />

    <flux:error name="notifications" />
</flux:field>
```

### Fieldset
Group related switches within a fieldset.

```html
<flux:fieldset>
    <flux:legend>Email notifications</flux:legend>

    <div class="space-y-4">
        <flux:switch wire:model.live="communication" label="Communication emails" description="Receive emails about your account activity." />

        <flux:separator variant="subtle" />

        <flux:switch wire:model.live="marketing" label="Marketing emails" description="Receive emails about new products, features, and more." />

        <flux:separator variant="subtle" />

        <flux:switch wire:model.live="social" label="Social emails" description="Receive emails for friend requests, follows, and more." />

        <flux:separator variant="subtle" />

        <flux:switch wire:model.live="security" label="Security emails" description="Receive emails about your account activity and security." />
    </div>
</flux:fieldset>
```

### Left align
Left align switches for more compact layouts using the align prop.

```html
<flux:fieldset>
    <flux:legend>Email notifications</flux:legend>

    <div class="space-y-3">
        <flux:switch label="Communication emails" align="left" />

        <flux:switch label="Marketing emails" align="left" />

        <flux:switch label="Social emails" align="left" />

        <flux:switch label="Security emails" align="left" />
    </div>
</flux:fieldset>
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the switch to a Livewire property. See the wire:model documentation for more information. |
| label | Label text displayed above the switch. When provided, wraps the switch in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the switch. When provided alongside label, appears between the label and switch within the flux:field wrapper. See the field component. |
| align | Alignment of the switch relative to its label. Options: right|start (default), left|end. |
| disabled | Prevents user interaction with the switch. |


| Attribute | Description |
| --- | --- |
| data-flux-switch | Applied to the root element for styling and identification. |
| data-checked | Applied when the switch is in the "on" state. |



---

## Table

Display structured data in a condensed, searchable format.

```html
<flux:table :paginate="$this->orders">
    <flux:table.columns>
        <flux:table.column>Customer</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'date'" :direction="$sortDirection" wire:click="sort('date')">Date</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
        <flux:table.column sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($this->orders as $order)
            <flux:table.row :key="$order->id">
                <flux:table.cell class="flex items-center gap-3">
                    <flux:avatar size="xs" src="{{ $order->customer_avatar }}" />

                    {{ $order->customer }}
                </flux:table.cell>

                <flux:table.cell class="whitespace-nowrap">{{ $order->date }}</flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" :color="$order->status_color" inset="top bottom">{{ $order->status }}</flux:badge>
                </flux:table.cell>

                <flux:table.cell variant="strong">{{ $order->amount }}</flux:table.cell>

                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>

<!-- Livewire component example code...
    use \Livewire\WithPagination;

    public $sortBy = 'date';
    public $sortDirection = 'desc';

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function orders()
    {
        return \App\Models\Order::query()
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(5);
    }
-->
```

### Simple
The primary table example above is a full-featured table with sorting, pagination, etc. Here's a clean example of a simple data table that you can use as a simpler starting point.

```html
<flux:table>
    <flux:table.columns>
        <flux:table.column>Customer</flux:table.column>
        <flux:table.column>Date</flux:table.column>
        <flux:table.column>Status</flux:table.column>
        <flux:table.column>Amount</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        <flux:table.row>
            <flux:table.cell>Lindsey Aminoff</flux:table.cell>
            <flux:table.cell>Jul 29, 10:45 AM</flux:table.cell>
            <flux:table.cell><flux:badge color="green" size="sm" inset="top bottom">Paid</flux:badge></flux:table.cell>
            <flux:table.cell variant="strong">$49.00</flux:table.cell>
        </flux:table.row>

        <flux:table.row>
            <flux:table.cell>Hanna Lubin</flux:table.cell>
            <flux:table.cell>Jul 28, 2:15 PM</flux:table.cell>
            <flux:table.cell><flux:badge color="green" size="sm" inset="top bottom">Paid</flux:badge></flux:table.cell>
            <flux:table.cell variant="strong">$312.00</flux:table.cell>
        </flux:table.row>

        <flux:table.row>
            <flux:table.cell>Kianna Bushevi</flux:table.cell>
            <flux:table.cell>Jul 30, 4:05 PM</flux:table.cell>
            <flux:table.cell><flux:badge color="zinc" size="sm" inset="top bottom">Refunded</flux:badge></flux:table.cell>
            <flux:table.cell variant="strong">$132.00</flux:table.cell>
        </flux:table.row>

        <flux:table.row>
            <flux:table.cell>Gustavo Geidt</flux:table.cell>
            <flux:table.cell>Jul 27, 9:30 AM</flux:table.cell>
            <flux:table.cell><flux:badge color="green" size="sm" inset="top bottom">Paid</flux:badge></flux:table.cell>
            <flux:table.cell variant="strong">$31.00</flux:table.cell>
        </flux:table.row>
    </flux:table.rows>
</flux:table>
```

### Pagination
Allow users to navigate through different pages of data by passing in any model paginator to the paginate prop.

```html
<!-- $orders = \App\Models\Order::paginate(5) -->

<flux:table :paginate="$orders">
    <!-- ... -->
</flux:table>
```

### Sortable
Allow users to sort rows by specific columns using a combination of the sortable, sorted, and direction props.

```html
<flux:table>
    <flux:table.columns>
        <flux:table.column>Customer</flux:table.column>
        <flux:table.column sortable sorted direction="desc">Date</flux:table.column>
        <flux:table.column sortable>Amount</flux:table.column>
    </flux:table.columns>

    <!-- ... -->
</flux:table>
```


| Customer | Date | Status | Amount |  |
| --- | --- | --- | --- | --- |
| Gustavo Mango | Jul 31, 9:50 AM | Paid | $162.00 | View invoice
    





            
    Refund
    






            
    Archive |
| Desirae George | Jul 31, 12:08 PM | Paid | $32.00 | View invoice
    





            
    Refund
    






            
    Archive |
| Emery Madsen | Jul 31, 11:50 AM | Paid | $163.00 | View invoice
    





            
    Refund
    






            
    Archive |
| [email protected] | Jul 31, 11:15 AM | Incomplete | $29.00 | View invoice
    





            
    Refund
    






            
    Archive |
| Kaiya Bator | Jul 31, 11:08 AM | Failed | $72.00 | View invoice
    





            
    Refund
    






            
    Archive |


| Customer | Date | Status | Amount |
| --- | --- | --- | --- |
| Lindsey Aminoff | Jul 29, 10:45 AM | Paid | $49.00 |
| Hanna Lubin | Jul 28, 2:15 PM | Paid | $312.00 |
| Kianna Bushevi | Jul 30, 4:05 PM | Refunded | $132.00 |
| Gustavo Geidt | Jul 27, 9:30 AM | Paid | $31.00 |


| Customer | Date | Amount |
| --- | --- | --- |
| Gustavo Mango | Jul 31, 9:50 AM | $162.00 |
| Desirae George | Jul 31, 12:08 PM | $32.00 |
| Emery Madsen | Jul 31, 11:50 AM | $163.00 |
| [email protected] | Jul 31, 11:15 AM | $29.00 |


| Prop | Description |
| --- | --- |
| paginate | A Laravel paginator instance to enable pagination. |


| Attribute | Description |
| --- | --- |
| data-flux-table | Applied to the root element for styling and identification. |


| Slot | Description |
| --- | --- |
| default | The table columns. |


| Prop | Description |
| --- | --- |
| align | Alignment of the column content. Options: start, center, end. |
| sortable | Enables sorting functionality for the column. |
| sorted | Indicates this column is currently being sorted. |
| direction | Sort direction when column is sorted. Options: asc, desc. |


| Slot | Description |
| --- | --- |
| default | The table rows. |


| Prop | Description |
| --- | --- |
| key | An alias for wire:key: the unique identifier for the row. |


| Slot | Description |
| --- | --- |
| default | The table cells for this row. |


| Prop | Description |
| --- | --- |
| align | Alignment of the cell content. Options: start, center, end. |
| variant | Visual style of the cell. Options: default, strong. |



---

## Tabs

Organize content into separate panels within a single container. Easily switch between sections without leaving the page.

```html
<flux:tab.group>
    <flux:tabs wire:model="tab">
        <flux:tab name="profile">Profile</flux:tab>
        <flux:tab name="account">Account</flux:tab>
        <flux:tab name="billing">Billing</flux:tab>
    </flux:tabs>

    <flux:tab.panel name="profile">...</flux:tab.panel>
    <flux:tab.panel name="account">...</flux:tab.panel>
    <flux:tab.panel name="billing">...</flux:tab.panel>
</flux:tab.group>
```

### With icons
Associate tab labels with icons to visually distinguish different sections.

```html
<flux:tab.group>
    <flux:tabs>
        <flux:tab name="profile" icon="user">Profile</flux:tab>
        <flux:tab name="account" icon="cog-6-tooth">Account</flux:tab>
        <flux:tab name="billing" icon="banknotes">Billing</flux:tab>
    </flux:tabs>

    <flux:tab.panel name="profile">...</flux:tab.panel>
    <flux:tab.panel name="account">...</flux:tab.panel>
    <flux:tab.panel name="billing">...</flux:tab.panel>
</flux:tab.group>
```

### Padded edges
By default, the tabs will have no horizontal padding around the edges. If you want to add padding you can do by adding Tailwind utilities to the tabs and/or tab.panel components.

```html
<flux:tabs class="px-4">
    <flux:tab name="profile">Profile</flux:tab>
    <flux:tab name="account">Account</flux:tab>
    <flux:tab name="billing">Billing</flux:tab>
</flux:tabs>
```

### Segmented tabs
Tab through content with visually separated, button-like tabs. Ideal for toggling between views inside a container with a constrained width.

```html
<flux:tabs variant="segmented">
    <flux:tab>List</flux:tab>
    <flux:tab>Board</flux:tab>
    <flux:tab>Timeline</flux:tab>
</flux:tabs>
```

### Segmented with icons
Combine segmented tabs with icon prefixes.

```html
<flux:tabs variant="segmented">
    <flux:tab icon="list-bullet">List</flux:tab>
    <flux:tab icon="squares-2x2">Board</flux:tab>
    <flux:tab icon="calendar-days">Timeline</flux:tab>
</flux:tabs>
```

### Small segmented tabs
For more compact layouts, you can use the size="sm" prop to make the tabs smaller.

```html
<flux:tabs variant="segmented" size="sm">
    <flux:tab>Demo</flux:tab>
    <flux:tab>Code</flux:tab>
</flux:tabs>
```

### Pill tabs
Tab through content with visually separated, pill-like tabs.

```html
<flux:tabs variant="pills">
    <flux:tab>List</flux:tab>
    <flux:tab>Board</flux:tab>
    <flux:tab>Timeline</flux:tab>
</flux:tabs>
```

### Dynamic tabs
If you need, you can dynamically generate additional tabs and panels in your Livewire component. Just make sure you use matching names for the new tabs and panels.

```html
<flux:tab.group>
    <flux:tabs>
        @foreach($tabs as $id => $tab)
            <flux:tab :name="$id">{{ $tab }}</flux:tab>
        @endforeach

        <flux:tab icon="plus" wire:click="addTab" action>Add tab</flux:tab>
    </flux:tabs>

    @foreach($tabs as $id => $tab)
        <flux:tab.panel :name="$id">
            <!-- ... -->
        </flux:tab.panel>
    @endforeach
</flux:tab.group>

<!-- Livewire component example code...
    public array $tabs = [
        'tab-1' => 'Tab #1',
        'tab-2' => 'Tab #2',
    ];

    public function addTab(): void
    {
        $id = 'tab-' . str()->random();

        $this->tabs[$id] = 'Tab #' . count($this->tabs) + 1;
    }
-->
```


| Slot | Description |
| --- | --- |
| default | The tabs and panels components. |


| Prop | Description |
| --- | --- |
| wire:model | Binds the active tab to a Livewire property. See wire:model documentation |
| variant | Visual style of the tabs. Options: default, segmented, pills. |
| size | Size of the tabs. Options: base (default), sm. |


| Slot | Description |
| --- | --- |
| default | The individual tab components. |


| Attribute | Description |
| --- | --- |
| data-flux-tabs | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| name | Unique identifier for the tab, used to match with its panel. |
| icon | Name of the icon to display at the start of the tab. |
| icon:trailing | Name of the icon to display at the end of the tab. |
| icon:variant | Variant of the icon. Options: outline, solid, mini, micro. |
| action | Converts the tab to an action button (used for "Add tab" functionality). |
| accent | If true, applies accent color styling to the tab. |
| size | Size of the tab (only applies when variant="segmented"). Options: base (default), sm. |
| disabled | Disables the tab. |


| Slot | Description |
| --- | --- |
| default | The tab label content. |


| Attribute | Description |
| --- | --- |
| data-flux-tab | Applied to the tab element for styling and identification. |
| data-selected | Applied when the tab is selected/active. |


| Prop | Description |
| --- | --- |
| name | Unique identifier matching the associated tab. |


| Slot | Description |
| --- | --- |
| default | The panel content displayed when the associated tab is selected. |


| Attribute | Description |
| --- | --- |
| data-flux-tab-panel | Applied to the panel element for styling and identification. |



---

## Text

Consistent typographical components like text and link.

```html
<flux:heading>Text component</flux:heading>
<flux:text class="mt-2">This is the standard text component for body copy and general content throughout your application.</flux:text>
```

### Size
Use standard Tailwind to control the size of the text so that you can more easily adapt to different screen sizes.

```html
<flux:text class="text-base">Base text size</flux:text>
<flux:text>Default text size</flux:text>
<flux:text class="text-xs">Smaller text</flux:text>
```

### Color
Use standard Tailwind to control the color of the text so that you can more easily adapt to different screen sizes.

```html
<flux:text variant="strong">Strong text color</flux:text>
<flux:text>Default text color</flux:text>
<flux:text variant="subtle">Muted text color</flux:text>
<flux:text color="blue">Colored text</flux:text>
```

### Link
Use the link component to create clickable text that navigates to other pages or resources.

```html
<flux:text>Visit our <flux:link href="#">documentation</flux:link> for more information.</flux:text>
```

### Link variants
Links can be styled differently based on their context and importance.

```html
<flux:link href="#">Default link</flux:link>
<flux:link href="#" variant="ghost">Ghost link</flux:link>
<flux:link href="#" variant="subtle">Subtle link</flux:link>
```


| Prop | Description |
| --- | --- |
| size | Size of the text. Options: sm, default, lg, xl. Default: default. |
| variant | Text variant. Options: strong, subtle. Default: default. |
| color | Color of the text. Options: default, red, orange, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose. Default: default. |
| inline | If true, the text element will be a span instead of a p. |


| Prop | Description |
| --- | --- |
| href | The URL that the link points to. Required. |
| variant | Link style variant. Options: default, ghost, subtle. Default: default. |
| external | If true, the link will open in a new tab. |



---

## Textarea

Capture multi-line text input from users. Ideal for comments, descriptions, and feedback.

```html
<flux:textarea />
```

### With placeholder
Display a hint inside the textarea to guide users on what to enter.

```html
<flux:textarea
    label="Order notes"
    placeholder="No lettuce, tomato, or onion..."
/>
```

### Fixed row height
Customize the height of the textarea by passing a rows prop.

```html
<flux:textarea rows="2" label="Note" />
```

### Auto-sizing textarea
Using CSS's new field-sizing property, the textarea will automatically adjust its height to fit the content by passing in the rows="auto" prop.

```html
<flux:textarea rows="auto" />
```

### Configure resize
If you want to restrict the user from resizing the textarea, you can use the resize="none" prop.

```html
<flux:textarea resize="vertical" />
<flux:textarea resize="none" />
<flux:textarea resize="horizontal" />
<flux:textarea resize="both" />
```


| Prop | Description |
| --- | --- |
| wire:model | Binds the textarea to a Livewire property. See the wire:model documentation for more information. |
| placeholder | Placeholder text displayed when the textarea is empty. |
| label | Label text displayed above the textarea. When provided, wraps the textarea in a flux:field component with an adjacent flux:label component. See the field component. |
| description | Help text displayed below the textarea. When provided alongside label, appears between the label and textarea within the flux:field wrapper. See the field component. |
| description:trailing | The description provided will be displayed below the textarea instead of above it. |
| badge | Badge text displayed at the end of the flux:label component when the label prop is provided. |
| rows | Number of visible text lines. Use "auto" for automatic height adjustment. Default: 4. |
| resize | Control how the textarea can be resized. Options: vertical (default), horizontal, both, none. |
| invalid | If true, applies error styling to the textarea. |


| Attribute | Description |
| --- | --- |
| data-flux-textarea | Applied to the textarea element for styling and identification. |



---

## Toast

A message that provides feedback to users about an action or event, often temporary and dismissible.

```html
<body>
    <!-- ... -->

    <flux:toast />
</body>
```

### With heading
Use a heading to provide additional context for the toast.

```html
Flux::toast(
    heading: 'Changes saved.',
    text: 'You can always update this in your settings.',
);
```

### Variants
Use the variant prop to change the visual style of the toast.

```html
Flux::toast(variant: 'success', ...);
Flux::toast(variant: 'warning', ...);
Flux::toast(variant: 'danger', ...);
```

### Positioning
By default, the toast will appear in the bottom right corner of the page. You can customize this position using the position prop.

```html
<flux:toast position="top right" />

<!-- Customize top padding for things like navbars... -->
<flux:toast position="top right" class="pt-24" />
```

### Duration
By default, the toast will automatically dismiss after 5 seconds. You can customize this duration by passing a number of milliseconds to the duration prop.

```html
// 1 second...
Flux::toast(duration: 1000, ...);
```

### Permanent
Use a value of 0 as the duration prop to make the toast stay open indefinitely.

```html
// Show indefinitely...
Flux::toast(duration: 0, ...);
```


| Prop | Description |
| --- | --- |
| position | Position of the toast on the screen. Options: bottom right (default), bottom left, top right, top left. |
| duration | Duration in milliseconds before the toast auto-dismisses. Use 0 for permanent toasts. Default: 5000. |
| variant | Visual style of the toast. Options: success, warning, danger. |


| Parameter | Description |
| --- | --- |
| heading | Optional heading text for the toast. |
| text | Main content text of the toast. |
| variant | Visual style. Options: success, warning, danger. |
| duration | Duration in milliseconds. Use 0 for permanent toasts. Default: 5000. |


| Parameter | Description |
| --- | --- |
| message | A string containing the toast message. When using this simple form, the message becomes the toast's text content. |
| options | Alternatively, an object containing:
- heading: Optional title text
- text: Main message text
- variant: Visual style (success, warning, danger)
- duration: Display time in milliseconds |



---

## Tooltip

Provide additional information when users hover over or focus on an element.

```html
<flux:tooltip content="Settings">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>
```

### Info tooltip
In cases where a tooltip's content is essential, you should make it toggleable. This way, users on touch devices will be able to trigger it on click/press rather than hover.

```html
<flux:heading class="flex items-center gap-2">
    Tax identification number

    <flux:tooltip toggleable>
        <flux:button icon="information-circle" size="sm" variant="ghost" />

        <flux:tooltip.content class="max-w-[20rem] space-y-2">
            <p>For US businesses, enter your 9-digit Employer Identification Number (EIN) without hyphens.</p>
            <p>For European companies, enter your VAT number including the country prefix (e.g., DE123456789).</p>
        </flux:tooltip.content>
    </flux:tooltip>
</flux:heading>
```

### Position
Position tooltips around the element for optimal visibility. Choose from top, right, bottom, or left.

```html
<flux:tooltip content="Settings" position="top">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="right">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="bottom">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>

<flux:tooltip content="Settings" position="left">
    <flux:button icon="cog-6-tooth" icon:variant="outline" />
</flux:tooltip>
```

### Disabled buttons
By default, tooltips on disabled buttons won't be triggered because pointer events are disabled as well. However, as a workaround, you can target a wrapping element instead of the button directly.

```html
<flux:tooltip content="Cannot merge until reviewed by a team member">
    <div>
        <flux:button disabled icon="arrow-turn-down-right">Merge pull request</flux:button>
    </div>
</flux:tooltip>
```


| Prop | Description |
| --- | --- |
| content | Text content to display in the tooltip. Alternative to using the flux:tooltip.content component. |
| position | Position of the tooltip relative to the trigger element. Options: top (default), right, bottom, left. |
| align | Alignment of the tooltip. Options: center (default), start, end. |
| gap | Spacing between the trigger element and the tooltip. Default: 5px. |
| offset | Offset of the tooltip from the trigger element. Default: 0px. |
| toggleable | Makes the tooltip clickable instead of hover-only. Useful for touch devices. |
| interactive | Uses the proper ARIA attributes (aria-expanded and aria-controls) to signal that the tooltip has interactive content. |
| kbd | Keyboard shortcut hint displayed at the end of the tooltip. |


| Attribute | Description |
| --- | --- |
| data-flux-tooltip | Applied to the root element for styling and identification. |


| Prop | Description |
| --- | --- |
| kbd | Keyboard shortcut hint displayed at the end of the tooltip content. |



---

