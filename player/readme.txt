=== FWD Ultimate Video Player ===
Contributors: futurewebdesign
Donate link: https://fwdapps.net/p/uvp/
Tags: video player, youtube player, vimeo player, hls player, playlist player
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 11.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Responsive WordPress video player with playlists, streaming support, YouTube/Vimeo, subtitles, ads, 360/VR, Chromecast, and analytics.

== Description ==

<strong>FWD Ultimate Video Player</strong> is a powerful and unique responsive video player for WordPress and WooCommerce that uses playlists to play local, self-hosted or streaming video and audio files, YouTube, Vimeo, Vimeo Pro, live streaming, HLS, DASH MPEG, 360 degree video / VR, Google Adsense, VAST & VMAP, and much more.

It is designed for publishers, course creators, media websites, membership platforms, product pages, and any project that needs a professional HTML5 player with deep customization, monetization, analytics, and content-protection tools.

<a href="https://fwdapps.net/p/uvp/">Homepage</a> | <a href="https://fwdapps.net/p/uvp/demos.html">Demos</a> | <a href="https://fwdapps.net/p/uvp/api.html">API</a> | <a href="https://fwdapps.net/contact">Support</a>


== Main Features ==

### Core Player Features
- Responsive layout, fully adaptable to any screen size and device.
- Desktop and mobile optimized playback.
- Multiple instances on the same page, with instance-aware playback coordination. Example: <a href="https://fwdapps.net/p/uvp/multiple-instances.html">multiple instances</a>.
- Multiple display types including responsive, fixed, <a href="https://fwdapps.net/p/uvp/sticky.html">sticky</a>, <a href="https://fwdapps.net/p/uvp/fullscreen.html">fullscreen</a>, <a href="https://fwdapps.net/p/uvp/lightbox.html">lightbox</a>, and popup modes.
- Optional deeplinking with unique shareable URLs for the current playlist and current video.
- Lazy scrolling / loading so the player initializes only when visible in the viewport.
- Resume / remember playback position when the browser is closed and reopened.
- URL timestamp support to start and/or stop playback from the URL. Example: <a href="https://fwdapps.net/p/uvp/timestamp.html#/?playlistId=0&videoId=0&t=0m0h20s&e=0m0h40s">timestamp demo</a>.
- Optional go fullscreen on play. Example: <a href="https://fwdapps.net/p/uvp/go-full-screen-on-play.html">go fullscreen on play</a>.
- Fill entire video screen and poster screen for full-width / gap-free presentation.
- Double click and double tap gestures for fullscreen toggle and 10-second seek.
- Keyboard support: fullscreen, play/pause, mute, volume, and seek shortcuts.
- Custom right-click context menu with copy video URL, copy URL at current time, and fullscreen actions.
- Powerful API with methods and events for play, pause, stop, scrub, volume, and more. Example: <a href="https://fwdapps.net/p/uvp/api.html">API demo</a>.

### Supported Media Sources And Formats
- Self-hosted HTML5 video and audio.
- Local or external .mp4 and .mp3 playback.
- YouTube single videos, playlists, and channels.
- Vimeo and Vimeo Pro videos, albums, playlists, and showcases.
- Apple HLS / .m3u8 live streaming with adaptive bitrate support.
- MPEG DASH / .mpd live streaming.
- Google Drive media. Example: <a href="https://fwdapps.net/p/uvp/google-drive.html">Google Drive demo</a>.
- Amazon S3, Dropbox, and other cloud or external sources.
- One-format video and audio workflow where a single .mp4 or .mp3 source can be used across browsers.
- Various video and audio formats in mixed playlists.

### Streaming, Quality, And Audio Features
- Multiple video quality levels such as 720p, 1080p, 2160p, and more. Example: <a href="https://fwdapps.net/p/uvp/multiple-video-qualitiy.html">multiple video quality</a>.
- Playback rate / speed selector. Example: <a href="https://fwdapps.net/p/uvp/playback-rate.html">playback rate demo</a>.
- HLS multiple quality levels.
- HLS multiple audio tracks. Example: <a href="https://fwdapps.net/p/uvp/hls-audio-tracks.html">HLS audio tracks</a>.
- Audio tracks support for .mp4 video where browser support is available.
- Real-time audio spectrum visualizer for .mp3 playback. Example: <a href="https://fwdapps.net/p/uvp/spectrum.html">spectrum visualizer</a>.
- Chromecast support for MP4, MP3, and streaming media. Example: <a href="https://fwdapps.net/p/uvp/chromecast.html">Chromecast demo</a>.

### 360 And VR Features
- 360 degree panoramic video support on desktop and mobile. Example: <a href="https://fwdapps.net/p/uvp/360.html">360 video demo</a>.
- Panoramic 360 VR and stereoscopic video support using WebXR.
- Cardboard mode and immersive VR playback support. Example: <a href="https://fwdapps.net/p/uvp/vr.html">VR demo</a>.

### Playlist Features
- Support for unlimited playlists and unlimited videos per playlist.
- Playlist creation through HTML markup / database, XML, video folder, audio folder, YouTube playlist or channel, Vimeo album or showcase, and mixed playlists.
- HTML playlists: <a href="https://fwdapps.net/p/uvp/html-playlist.html">HTML markup playlist</a>.
- XML playlists: <a href="https://fwdapps.net/p/uvp/xml-playlist.html">XML playlist</a>.
- Folder playlists: <a href="https://fwdapps.net/p/uvp/folder-playlist.html">audio folder playlist</a>.
- YouTube playlists: <a href="https://fwdapps.net/p/uvp/youtube-playlist.html">YouTube playlist</a>.
- Vimeo playlists: <a href="https://fwdapps.net/p/uvp/vimeo-playlist.html">Vimeo playlist</a>.
- Playlists can be created manually or generated from a database.
- Two main playlist selection modes: dropdown selector or fullscreen thumbnail window.
- Playlists live search for both main playlists and current playlist items.
- Playlists window auto open. Example: <a href="https://fwdapps.net/p/uvp/open-playlists.html">open playlists demo</a>.
- Playlist position on the right or bottom. Examples: <a href="https://fwdapps.net/p/uvp/minimal-skin-dark.html">right playlist</a> and <a href="https://fwdapps.net/p/uvp/playlist-bottom-dark.html">bottom playlist</a>.
- Playlist without thumbnails. Example: <a href="https://fwdapps.net/p/uvp/playlist-without-thumbnails.html">playlist without thumbnails</a>.
- Playlist with only thumbnails. Example: <a href="https://fwdapps.net/p/uvp/playlist-only-thumbnails.html">playlist only thumbnails</a>.
- Customizable playlist width, thumbnail size, and text styling. Example: <a href="https://fwdapps.net/p/uvp/large-playlist.html">large playlist</a>.
- Scroll playlist on mouse move. Example: <a href="https://fwdapps.net/p/uvp/scroll-on-move.html">scroll on mouse move</a>.

### Skins, Themes, And UI Customization
- Vector or image-based skins.
- Graphics (.png) skins and vector font skins.
- 5 graphics skins and 1 vector skin included, with dark and white variations.
- HEX / CSS skin color support so UI elements can be recolored from the admin.
- Customizable skin and theme color system.
- Advanced control bar settings.
- Optional buttons and UI modules: play, pause, fullscreen, playlist, rewind, next/previous, shuffle, loop, download, share, embed, volume controls, and more.
- Video poster support.
- Watermark logo with custom position, optional visibility rules, and link on click.
- Video info window for per-video info content. Example: <a href="https://fwdapps.net/p/uvp/info-window.html">info window demo</a>.
- Embed and share window for the current video. Example: <a href="https://fwdapps.net/p/uvp/embed-and-share.html">embed and share demo</a>.

### Preview, Subtitle, And Accessibility Features
- Multiple subtitles per video.
- Subtitle formats: .txt, .srt, and .vtt.
- Subtitle selector for switching subtitles during playback. Example: <a href="https://fwdapps.net/p/uvp/multiple-subtitles.html">multiple subtitles</a>.
- Thumbnails .vtt preview over the progress bar. Example: <a href="https://fwdapps.net/p/uvp/thumbnails-preview.html">.vtt thumbnail preview</a>.
- Live auto-generated thumbnails preview from video. Example: <a href="https://fwdapps.net/p/uvp/thumbnails-preview-live.html">live thumbnails preview</a>.
- Video tutorial for live thumbnails: <a href="https://www.youtube.com/watch?v=XNhpC0dndAg">watch tutorial</a>.
- Annotations with full content control, HTML/CSS formatting, interactive links, and JavaScript callbacks. Example: <a href="https://fwdapps.net/p/uvp/annotations.html">annotations demo</a>.
- Video cuepoints to execute JavaScript at specified playback times. Example: <a href="https://fwdapps.net/p/uvp/cuepoints.html">cuepoints demo</a>.

### Security, Restriction, And Content Protection
- Encrypt video source / path to help prevent exposing the source URL in page source.
- Password protected videos. Example: <a href="https://fwdapps.net/p/uvp/private.html">private videos demo</a>.
- Play only if logged in. Example: <a href="https://fwdapps.net/p/uvp/login.html">login required demo</a>.
- Digital fingerprint stamp for tracing recorded content usage. Tutorial: <a href="https://www.youtube.com/watch?v=5ccWSz1Mr_0">digital fingerprint tutorial</a>.

### Advertising And Monetization
- Pre-roll, mid-roll, and post-roll advertising.
- Video, audio, YouTube, Vimeo, Vimeo Pro, iframe, HLS, DASH, and image ads.
- Popup commercial ads with configurable show/hide times. Example: <a href="https://fwdapps.net/p/uvp/popup-ads.html">popup ads demo</a>.
- Popup advertisement window on pause using iframe content. Example: <a href="https://fwdapps.net/p/uvp/overlay-ad.html">overlay advertisement window</a>.
- Non-linear Google Adsense support.
- DFP / Google Doubleclick IMA tags support. Example: <a href="https://fwdapps.net/p/uvp/adsense.html">Adsense / IMA demo</a>.
- VAST and VMAP support with monetization workflows. Example: <a href="https://fwdapps.net/p/uvp/vast.html">VAST / VMAP demo</a>.
- IMA SDK for HTML5 and Google Adsense Doubleclick integration.
- VPAID support.

### Playback Control And User Experience
- A to B video loop / AB loop. Example: <a href="https://fwdapps.net/p/uvp/a-to-b-loop.html">A to B loop demo</a>.
- Autoplay with mute to comply with browser autoplay policies. Example: <a href="https://fwdapps.net/p/uvp/autoplay.html">autoplay demo</a>.
- Loop and shuffle options.
- Download video button support.
- Start at random video option.
- Start / stop playback at specified time.
- Start volume value setting.
- Click video to play / pause.
- Advanced video settings and advanced button settings.

### Analytics, Integration, And WordPress Features
- Google Analytics integration. Tutorial: <a href="https://www.youtube.com/watch?v=cs_j1pWSbEY">Google Analytics tutorial</a>.
- WooCommerce support for product pages.
- WordPress admin with modular configuration and shortcode generator.
- Custom post types integration.
- Gutenberg and classic editor friendly shortcode workflow.
- Shortcode generator metabox inside WordPress.
- Detailed documentation and video tutorials. Example tutorial: <a href="https://www.youtube.com/watch?v=WtlBO7KJGi4">WordPress tutorial</a>.

### Support And Ongoing Development
- Constant development and updates.
- Direct support from the UVP developer.
- Trusted by 25,000+ clients.
- Quality checked and extensively used in production environments.

== Supported Use Cases ==

- Video courses and training portals
- Membership and paid content websites
- Product demo and marketing pages
- Audio playlists and podcast pages
- Streaming media websites
- Protected client dashboards
- WooCommerce product media galleries
- 360 / VR immersive showcases

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the Plugins menu in WordPress.
3. Open the Ultimate Video Player admin panel and create a preset and playlist.
4. Insert the shortcode into a page, post, product, or template.

== Shortcode ==

Basic example:

[fwd-ultimate-video-player preset_id="1" playlist_id="1" start_at_playlist="0" start_at_video="0"]

Shortcode attributes:
- preset_id: The preset configuration ID.
- playlist_id: The playlist ID to load.
- start_at_playlist: Optional playlist index to open first.
- start_at_video: Optional video index to start first.

== External services ==

This plugin uses the following third-party/external services:

1. Google Analytics (Google Tag Manager script)
- What it is used for: optional playback analytics/events.
- When it is used: only if a Google Analytics Measurement ID is configured in the player preset.
- What data is sent: playback analytics event data (for example video URL, video name, played percentage, playback position, duration, fullscreen state, and download/ad events).
- Service provider: Google.
- Terms of service: https://policies.google.com/terms
- Privacy policy: https://policies.google.com/privacy

2. YouTube Data API v3
- What it is used for: load YouTube playlists/channels metadata into the player playlist.
- When it is used: when a YouTube playlist or channel source is configured.
- What data is sent: YouTube channel ID or playlist ID and the configured YouTube API key from plugin settings; request is made from the visitor browser.
- Service provider: Google (YouTube).
- Terms of service: https://www.youtube.com/t/terms
- Privacy policy: https://policies.google.com/privacy

3. YouTube IFrame Player API
- What it is used for: embedded playback and control of YouTube videos inside the player.
- When it is used: when the current source is a YouTube video.
- What data is sent: the visitor browser connects to YouTube to load the player API and video resources.
- Service provider: Google (YouTube).
- Terms of service: https://www.youtube.com/t/terms
- Privacy policy: https://policies.google.com/privacy

4. Vimeo Player API
- What it is used for: embedded playback and control of Vimeo videos inside the player.
- When it is used: when the current source is a Vimeo video.
- What data is sent: the visitor browser connects to Vimeo to load the player API and video resources.
- Service provider: Vimeo.
- Terms of service: https://vimeo.com/terms
- Privacy policy: https://vimeo.com/privacy

5. Google IMA SDK
- What it is used for: loading and rendering ad flows (IMA/DoubleClick/VAST/VMAP related playback).
- When it is used: when ad features that require IMA are enabled.
- What data is sent: ad request and playback context data from the visitor browser as required by the configured ad setup.
- Service provider: Google.
- Terms of service: https://policies.google.com/terms
- Privacy policy: https://policies.google.com/privacy

6. Google Cast Sender SDK (Chromecast)
- What it is used for: Chromecast sender support from supported browsers/devices.
- When it is used: when the Chromecast feature/button is enabled and available.
- What data is sent: cast session and media-cast related data from the visitor browser/device.
- Service provider: Google.
- Terms of service: https://policies.google.com/terms
- Privacy policy: https://policies.google.com/privacy

7. Google Fonts API
- What it is used for: loading the Roboto font used by the player stylesheet.
- When it is used: when the plugin stylesheet is loaded on the page.
- What data is sent: the visitor browser requests font CSS/font files from Google Fonts.
- Service provider: Google.
- Terms of service: https://policies.google.com/terms
- Privacy policy: https://policies.google.com/privacy

8. Social sharing endpoints (Facebook, X/Twitter, LinkedIn, Buffer, Reddit, Tumblr, Digg)
- What it is used for: opening social share windows from the player share UI.
- When it is used: when a visitor clicks a social share button in the player.
- What data is sent: the current page URL is passed to the selected sharing service.
- Service providers and legal links:
- Facebook terms: https://www.facebook.com/terms.php
- Facebook privacy: https://www.facebook.com/privacy/policy
- X terms: https://x.com/en/tos
- X privacy: https://x.com/en/privacy
- LinkedIn terms: https://www.linkedin.com/legal/user-agreement
- LinkedIn privacy: https://www.linkedin.com/legal/privacy-policy
- Buffer terms: https://buffer.com/legal/terms
- Buffer privacy: https://buffer.com/legal/privacy
- Reddit terms: https://www.redditinc.com/policies/user-agreement
- Reddit privacy: https://www.reddit.com/policies/privacy-policy
- Tumblr terms: https://www.tumblr.com/policy/en/terms-of-service
- Tumblr privacy: https://www.tumblr.com/privacy/en
- Digg website: http://digg.com/

== Video tutrials ==

- Main WordPress setup and usage: https://www.youtube.com/watch?v=WtlBO7KJGi4
- Installation (timestamped): https://www.youtube.com/watch?v=WtlBO7KJGi4?t=1
- WooCommerce setup: https://www.youtube.com/watch?v=SxAWCjNAKdQ
- 360 / VR workflow: https://www.youtube.com/watch?v=oL8oWo9UPGA
- Fingerprint stamp: https://www.youtube.com/watch?v=5ccWSz1Mr_0
- Google Drive media setup: https://www.youtube.com/watch?v=YK3YucN2PYc
- Google Adsense setup: https://www.youtube.com/watch?v=PXsfBh74ho4
- Thumbnails preview (.vtt): https://www.youtube.com/watch?v=hqTNCPE1zYE
- Live thumbnails preview: https://www.youtube.com/watch?v=XNhpC0dndAg
- Chromecast: https://www.youtube.com/watch?v=j_7x3pFSg24
- YouTube API key: https://www.youtube.com/watch?v=whcjAjftBL0
- Google Analytics integration: https://www.youtube.com/watch?v=cs_j1pWSbEY
- Open in lightbox: https://www.youtube.com/watch?v=tEqE31YReX8

== Frequently Asked Questions ==

= Does it work with Gutenberg? =
Yes. You can use the shortcode in any block, including paragraph, shortcode, custom HTML, or Classic blocks.

= Can it play YouTube, Vimeo, and self-hosted files in the same product? =
Yes. UVP supports mixed playlists with self-hosted video/audio, YouTube, Vimeo, Vimeo Pro, HLS, DASH, Google Drive, Amazon S3, Dropbox, and more.

= Does it support streaming formats? =
Yes. UVP supports Apple HLS / .m3u8 and MPEG DASH / .mpd live streaming, including multiple quality levels and HLS multiple audio tracks.

= Does it support ads and monetization? =
Yes. UVP supports pre-roll, mid-roll, post-roll, popup ads, overlay ads on pause, Google Adsense, DFP / Doubleclick IMA tags, VAST, VMAP, VPAID, and non-linear ads.

= Can I use protected or members-only videos? =
Yes. You can use password-protected videos, login-required playback, encrypted media paths, and digital fingerprint tools.

= Does it support 360 and VR videos? =
Yes. UVP supports panoramic 360 video, VR, stereoscopic playback, WebXR, and Cardboard-compatible immersive experiences.

= Can I customize the interface? =
Yes. UVP includes vector and image skins, HEX color customization, configurable controls, playlist layouts, watermark logo options, posters, and advanced control bar settings.

== Links ==

- <a href="https://fwdapps.net/p/uvp/">Product page</a>
- <a href="https://fwdapps.net/p/uvp/demos.html">Live demos</a>
- <a href="https://fwdapps.net/p/uvp/api.html">API examples</a>
- <a href="https://fwdapps.net/product/ultimate-video-player-wp">Buy WordPress plugin</a>
- <a href="https://fwdapps.net/product/ultimate-video-player">Buy JavaScript version</a>
- <a href="https://fwdapps.net/contact">Contact and support</a>


== Development ==
This plugin’s full source code is publicly available for transparency and verification.  
Developed and maintained by [FutureWebDesign](https://fwdapps.net).

- [Source code & build tools](https://fwdapps.net/p/uvp/source.zip) — includes original uncompiled files and Vite build configuration.
- Distributed plugin uses a compiled build generated by Vite for optimal performance.
