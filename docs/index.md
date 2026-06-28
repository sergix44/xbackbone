---
layout: home

hero:
  text: Self-hosted file & media sharing
  tagline: A simple, lightweight, modern platform with first-class support for instant sharing tools. Upload images, video, audio, PDFs, code and any file, then share them through clean short links with rich embeds.
  image:
    src: /logo-text.png
    alt: XBackBone
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Installation
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/SergiX44/XBackBone

features:
  - icon: 🚀
    title: Capture-tool ready
    details: One-click ShareX uploader configuration, with more capture clients on the way. Start sharing instantly.
    link: /clients/
    linkText: Supported clients
  - icon: 🖼️
    title: Wide media support
    details: Inline previews for images, video, audio (waveform), PDFs and syntax-highlighted code, plus pastes and link shortening.
  - icon: ☁️
    title: Multiple storage backends
    details: Local disk, Amazon S3 (and S3-compatible services), FTP and SFTP — switch without touching your data model.
    link: /guide/storage
    linkText: Configure storage
  - icon: 🧬
    title: Content-addressed storage
    details: Uploads are de-duplicated by content fingerprint, so identical files are stored only once.
  - icon: 🔒
    title: Private & public uploads
    details: Per-resource visibility with optional password protection and automatic expiration.
  - icon: 🔌
    title: REST API
    details: A versioned API with token authentication and per-instance, auto-generated OpenAPI documentation.
    link: /clients/api
    linkText: Read the API reference
  - icon: 🔑
    title: Modern authentication
    details: Registration, email verification, password reset, two-factor auth and passkeys (WebAuthn).
  - icon: 👥
    title: User management
    details: Admin roles, per-user disk quotas and usage statistics out of the box.
  - icon: 🎛️
    title: Runtime settings
    details: Toggle sign-ups, the default theme and more from the admin area at runtime — no redeploy required.
  - icon: 🎨
    title: Theming
    details: Switchable daisyUI themes and a localization-ready interface.
  - icon: 🧙
    title: Guided web installer
    details: Set up the database, storage and admin account straight from your browser.
    link: /guide/installation
    linkText: Install now
  - icon: 🔁
    title: Legacy import
    details: Migrate users and uploads from a legacy XBackBone instance, with old links transparently redirected.
    link: /guide/legacy-import
    linkText: Import data
---
