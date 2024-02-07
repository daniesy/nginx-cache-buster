<img src="/assets/logo.png" width="250" />

# Nginx Cache Buster

## Description:

The Nginx Cache Buster plugin provides a convenient solution for clearing the cache of individual pages or all pages on your WordPress website that utilize Nginx with fastcgi_cache enabled. By utilizing this plugin, you can ensure that your website's content remains fresh and up-to-date for your visitors.

## Features:

- Clear cache for individual pages: Easily clear the cache for specific pages from within your WordPress dashboard.
- Clear cache for all pages: Quickly flush the entire cache for all pages with just a single click.
- Seamless integration with Nginx: Works seamlessly with Nginx servers configured with fastcgi_cache enabled.
- Minimal configuration required: The plugin is designed to work with the default Nginx configuration, minimizing setup time and hassle.

## Requirements:

- Nginx server with fastcgi_cache enabled.
- WordPress installation running on the Nginx server.
- In order for busting cache to work, the following fastcgi_cache settings are required
  - The cache path: `fastcgi_cache_path /etc/nginx/cache levels=1:2 keys_zone=wpcache:100m inactive=60m;`
  - The cache key: `fastcgi_cache_key "$scheme$request_method$host$request_uri";`

## Usage:

- Install and activate the Nginx Cache Buster plugin.
- Navigate to the WordPress dashboard.
- To clear cache for individual pages, go to the pages list and click on the Bust cache button, under the page name.
- To clear cache for individual posts, go to the pages list and click on the Bust cache button, under the post name.
- To clear cache for all pages, go to the plugin settings page and click the "Bust all cache" button in the admin bar.

Ensure your Nginx server is properly configured with fastcgi_cache enabled to fully utilize the capabilities of this plugin.
