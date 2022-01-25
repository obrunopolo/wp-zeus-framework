# Create with React

## Why use React?

React.js is an open-source JavaScript library that is used for building user interfaces specially for single-page applications. It’s used for handling the view layer for web and mobile apps. React also allows us to create reusable UI components. React allows developers to create large web applications that can change data, without reloading the page. The main purpose of React is to be fast, scalable, and simple.

Today, React.js is used by many Fortune 500 companies. Facebook has full-time React development staff. They regularly release bug fixes, enhancements, blog posts, and documentation.

 > This documentation will not show how to use React itself, but how to integrate it into your application.

## How to use React with Zeus

The easiest way to use it is installing via `npm`:

```
npm install react react-dom
```

 > All commands should be executed in `lib` directory.

After installing, you need a component and somewhere to render it. Lets assume we have a custom page, where there is an empty `div` element.

```html
<div id="render-things-here"></div>
```

Now, we need to create a component to be rendered in that element.

 - `lib/ts/components/helloworld.tsx`

```tsx
import React from 'react';
import ReactDOM from 'react-dom';

export const HelloWorld = () => {
    return (
        <p>Hello world!</p>
    )
}

export const RenderHelloWorld = (selector: string) => {
    ReactDOM.render(<HelloWorld />, selector);
}

```

After that, all we need to do is to call `RenderHelloWorld` inside a file that runs in the custom page.

 - `lib/ts/my-entry.ts`

```ts
import { RenderHelloWorld } from './components/helloworld'

(($) => {
    $(window).on('load', () => {
        RenderHelloWorld('#render-things-here');
    });
})(jQuery);
```

 > See [create JS documentation](js.md) to understand how to create and load entries.
