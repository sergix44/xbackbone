import { defineConfig } from 'vitepress'

const description =
  'XBackBone is a simple, self-hosted, lightweight file and media sharing platform ' +
  'with first-class support for instant sharing tools.'

const repo = 'https://github.com/sergix44/xbackbone'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'en-US',
  title: 'XBackBone',
  titleTemplate: ':title · XBackBone',
  description,

  // Served from the domain root. Change to '/<repo>/' if you ever host it
  // under a GitHub Pages project subpath instead of a custom domain.
  base: '/',

  cleanUrls: true,
  lastUpdated: true,
  metaChunk: true,

  head: [
    ['link', { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#2f7df1' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:locale', content: 'en' }],
    ['meta', { property: 'og:title', content: 'XBackBone' }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:image', content: '/logo.png' }],
    ['meta', { name: 'twitter:card', content: 'summary' }],
  ],

  sitemap: {
    hostname: 'https://xbackbone.app',
  },

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.png',
    siteTitle: 'XBackBone',

    nav: [
      { text: 'Docs', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'Clients', link: '/clients/', activeMatch: '/clients/' },
      {
        text: 'More',
        items: [
          { text: 'Releases', link: `${repo}/releases` },
          { text: 'v3 docs', link: '/v3/', target: '_blank', rel: 'noreferrer' },
        ],
      },
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'What is XBackBone?', link: '/guide/getting-started' },
        ],
      },
      {
        text: 'Setup',
        items: [
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Storage backends', link: '/guide/storage' },
          { text: 'Upgrading', link: '/guide/upgrading' },
          { text: 'Importing', link: '/guide/legacy-import' },
        ],
      },
      {
        text: 'Clients',
        items: [
          { text: 'ShareX', link: '/clients/sharex' },
          { text: 'Xerahs', link: '/clients/xerahs' },
          { text: 'ScreenCloud', link: '/clients/screencloud' },
          { text: 'ishare', link: '/clients/ishare' },
          { text: 'Spectacle', link: '/clients/spectacle' },
          { text: 'macOS', link: '/clients/macos' },
          { text: 'CLI', link: '/clients/cli-scripts' },
          { text: 'REST API', link: '/clients/api' },
        ],
      },
      {
        text: 'Contributing',
        items: [
          { text: 'Developer guide', link: '/guide/developer' },
        ],
      },
    ],

    socialLinks: [{ icon: 'github', link: repo }],

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: `${repo}/edit/next/docs/:path`,
      text: 'Edit this page on GitHub',
    },

    footer: {
      message: 'Released under the Apache 2.0 License.',
      copyright: 'Copyright © 2018–present Sergio Brighenti',
    },
  },
})
