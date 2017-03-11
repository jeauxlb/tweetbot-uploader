# README
This is a group of scripts that will allow Tweetbot for iOS to upload to your own server.

The following features exist:

1. Automatic conversion of videos to GIFs with ffmpeg
    1. The link to these GIFs can be returned by adding gif=1 to the request URI
2. Automatic twitter handle watermarking by adding watermark=1 to the request URI
3. A high-res option for GIF conversion by adding highres=1 to the request URI

## Setup
The following folder structure is required within the /i/ directory:
- /gif/
- /orig/
