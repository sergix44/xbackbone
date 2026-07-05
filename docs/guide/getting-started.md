# What is XBackBone?

**XBackBone** is a simple, self-hosted, lightweight file and media sharing platform. Upload
images, GIFs, video, audio, PDFs, code and arbitrary files, then share them through clean short
links with rich social embeds — all from a server you control.

It pairs a modern web interface with one-click setup for screen-capture tools, so taking a
screenshot and getting a shareable link can be a single keypress.

## What you can do

- **Share almost anything.** Images, video, audio, PDFs, syntax-highlighted code, plus text
  *pastes* and shortened *links*.
- **Get clean, embed-friendly links.** Every upload gets a short URL that previews nicely on
  Discord, Telegram, social networks and chat apps.
- **Capture and upload in one step.** Connect a tool like [ShareX](/clients/) and your screenshots
  land on your instance automatically, with the link copied to your clipboard.
- **Keep things private.** Make uploads public or private, protect them with a password, or set
  them to expire automatically.
- **Stay organized.** Browse, search and manage everything you've shared from your dashboard.
- **Keep an eye on activity.** A timeline records your account activity — uploads, logins, token
  and passkey changes — and admins get an instance-wide feed.
- **Own your data.** Run XBackBone on your own server, with the storage backend of your choice.

## Your first upload

Once you have access to an instance (your own or one you've been invited to):

1. **Sign in** to your XBackBone instance.
2. From the **dashboard**, drag a file onto the upload area — or create a **paste** or a
   **shortened link** instead.
3. XBackBone gives you a **short share link**. Copy it and send it anywhere.
4. Open the resource to set its **visibility** (public or private), add an optional **password**,
   or give it an **expiration** date.

That's the whole loop: upload, share, manage.

## Connect a capture tool

For the fastest workflow, wire up a screen-capture client so sharing is automatic. XBackBone can
generate a ready-to-use configuration for you from the **Integrations** area of your profile.

See [Supported clients](/clients/) for the full list and setup steps.

## Run your own instance

XBackBone is self-hosted: you run it on your own server. It ships with a **guided web installer**
that sets up the database, storage and your admin account from the browser, so you can be up and
running in a few minutes.

<div class="tip custom-block" style="padding-top: 8px">

Ready to run your own instance? Head to the [Installation](/guide/installation) guide.

</div>

## Next steps

- [Installation](/guide/installation) — get an instance running.
- [Configuration](/guide/configuration) — tune environment and runtime settings.
- [Storage backends](/guide/storage) — local disk, S3, FTP or SFTP.
- [Supported clients](/clients/) — connect ShareX and other uploaders.
- [Developer guide](/guide/developer) — architecture, the REST API and contributing.
