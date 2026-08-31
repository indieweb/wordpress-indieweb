#!/usr/bin/env node

const TITLE_TO_SLUG_REPLACEMENTS = {
  '+': 'plus',
  '.': 'dot',
  '&': 'and',
  đ: 'd',
  ħ: 'h',
  ı: 'i',
  ĸ: 'k',
  ŀ: 'l',
  ł: 'l',
  ß: 'ss',
  ŧ: 't',
};

const TITLE_TO_SLUG_CHARS_REGEX = RegExp(
	  `[${Object.keys(TITLE_TO_SLUG_REPLACEMENTS).join('')}]`,
	  'g',
);

// Curated list of icons to include in the plugin (from Simple Icons only)
// Note: twitter, nostr, googlepodcasts are custom icons, not from Simple Icons
const curatedIcons = [
	// Explicitly mapped domains (from class-relme-domain-icon-map.php)
	'blogger', 'facebook', 'swarm', 'instagram', 'googleplay',
	'applepodcasts', 'pocket', 'flipboard', 'microdotblog', 'wordpress',
	'applemusic', 'bluesky', 'mastodon',
	// Profile fields
	'github', 'flickr', 'reddit',
	// Common social networks
	'medium', 'tumblr', 'youtube', 'linkedin', 'pinterest', 'snapchat', 'tiktok',
	'whatsapp', 'telegram', 'discord', 'threads', 'x',
	// Fediverse/IndieWeb
	'activitypub', 'pixelfed', 'peertube', 'lemmy', 'diaspora', 'pleroma', 'misskey', 'matrix',
	// Developer platforms
	'gitlab', 'codeberg', 'gitea', 'codepen', 'stackoverflow', 'npm', 'ycombinator',
	'orcid', 'keybase', 'gravatar',
	// Content/Creator platforms
	'substack', 'patreon', 'kofi', 'buymeacoffee', 'gumroad', 'etsy', 'bandcamp', 'soundcloud',
	// Media/Entertainment
	'spotify', 'lastdotfm', 'letterboxd', 'goodreads', 'vimeo', 'twitch', '500px', 'strava',
	// Design
	'dribbble', 'behance', 'figma', 'adobe',
	// Messaging
	'slack', 'signal', 'element', 'googlechat', 'zoom',
	// Other common
	'paypal', 'stripe', 'bitcoin', 'ethereum', 'rss',
];

// Minimal list for the minimal CSS (most common icons)
const minimalIcons = [
	'twitter', 'facebook', 'instagram', 'microdotblog', 'bluesky', 'github', 'flickr',
	'mastodon', 'wordpress', 'tumblr', 'blogger', 'medium', 'reddit', 'swarm'
];

const TITLE_TO_SLUG_RANGE_REGEX = /[^a-z0-9]/g;

/**
 * Converts a brand title into a slug/filename.
 * @param {String} title The title to convert
 */
const titleToSlug = (title) =>
  title
    .toLowerCase()
    .replace(
      TITLE_TO_SLUG_CHARS_REGEX,
      (char) => TITLE_TO_SLUG_REPLACEMENTS[char],
    )
    .normalize('NFD')
    .replace(TITLE_TO_SLUG_RANGE_REGEX, '');

// Generate SASS file from Simple Icons json data
// Get JSON from source file
var source = require('../../node_modules/simple-icons/_data/simple-icons.json');
var path = require('path');

// Filter to only include curated icons
var allIcons = source.icons;
source.icons = allIcons.filter(function(icon) {
    var slug = titleToSlug(icon.title.toLowerCase());
    return curatedIcons.includes(slug);
});

console.log('Filtered to ' + source.icons.length + ' curated icons (from ' + allIcons.length + ' total)');

// Loop through icons
for (var i = 0; i < source.icons.length; i++) {

    var hex = source.icons[i].hex;

    // Add red, green and blue values to the JSON object
    var red   = parseInt(hex.substr(0,2), 16) / 255;
    var green = parseInt(hex.substr(2,2), 16) / 255;
    var blue  = parseInt(hex.substr(4,2), 16) / 255;

    // Add hue to the JSON object
    var max = Math.max(red, green, blue);
    var min = Math.min(red, green, blue);
    var delta = max - min;
    source.icons[i].luminance = 100 * (max + min) / 2;
    if (delta === 0) {
        var hue = 0;
        source.icons[i].saturation = 0;
    } else {
        if (source.icons[i].luminance < 50) {
            source.icons[i].saturation = 100 * (max - min) / (max + min);
        } else {
            source.icons[i].saturation = 100 * (max - min) / (2 - max - min);
        }
        if (max === red) {
            var hue = ((green - blue) / delta) * 60;
            if (hue < 0) {
                hue += 360;
            }
        } else if (max === green) {
            var hue = (((blue - red) / delta) + 2) * 60;
        } else {
            var hue = (((red - green) / delta) + 4) * 60;
        }
    }
    source.icons[i].hue = hue;
}

// Sort icons by hue
for (var i = 0; i < source.icons.length; i++) {
    source.icons[i].hue += 90;
    source.icons[i].hue = source.icons[i].hue % 360;
}
source.icons.sort(function(a, b) {
    return parseFloat(a.hue) - parseFloat(b.hue);
});
var tmp = [];
for (var i = 0; i < source.icons.length; i++) {
    if (source.icons[i].luminance < 15) {
        tmp.push(source.icons[i]);
        source.icons.splice(i,1);
        i--;
    }
}
for (var i = 0; i < source.icons.length; i++) {
    if (source.icons[i].saturation < 25) {
        tmp.push(source.icons[i]);
        source.icons.splice(i,1);
        i--;
    }
}
tmp.sort(function(a, b) {
    return parseFloat(b.luminance) - parseFloat(a.luminance);
});
for (var i = 0; i < tmp.length; i++) {
    source.icons.push(tmp[i]);
}

// Read header and footer content into variables
var fs = require('fs');
function readFile(path, callback) {
    try {
        var filename = require.resolve(path);
        fs.readFile(filename, 'utf8', callback);
    } catch (e) {
        callback(e);
    }
}

var sass = "// Brand colors from simpleicons.org\n";
var min = "// Brand colors from simpleicons.org\n";
var names = "";
var textdomain = "indieweb";
sass += ".relme li a {\n";
min += ".relme li a {\n";
names += "<?php\n";
names += "/**\n";
names += " * Simple Icons name mappings.\n";
names += " *\n";
names += " * This file is auto-generated by static/js/simpleicons.js\n";
names += " *\n";
names += " * @package IndieWeb\n";
names += " */\n\n";
names += "/**\n";
names += " * Get the mapping of icon slugs to display names.\n";
names += " *\n";
names += " * @return array Array of icon slug => display name pairs.\n";
names += " */\n";
names += "function simpleicons_iw_get_names() {\n\treturn array(";
var maxNameLength = 0;

for (var i = 0; i < source.icons.length; i++) {
    var fileName = titleToSlug( source.icons[i].title.toLowerCase() );
    if (fileName.length > maxNameLength) {
        maxNameLength = fileName.length;
    }
}

// Sort icons alphabetically
source.icons.sort(function(a, b) {
    if (a.title < b.title) {
        return -1;
    }
    if (a.title > b.title) {
        return 1;
    }
    // names must be equal
    return 0;
});

for (var i = 0; i < source.icons.length; i++) {
    var fileName = titleToSlug( source.icons[i].title.toLowerCase() );
    spacing = "";
    if (fileName.length < maxNameLength) {
        spacing = " ".repeat(maxNameLength - fileName.length);
    }

    sass += "\n\t.svg-" + fileName.toLowerCase() + spacing + "{" + "\n\t\tcolor: #" + source.icons[i].hex.toUpperCase() + ";" + "\n\t}";
    if  ( minimalIcons.includes( fileName.toLowerCase() ) ) {
	min += "\n\t.svg-" + fileName.toLowerCase() + spacing + "{" + "\n\t\tcolor: #" + source.icons[i].hex.toUpperCase() + ";" + "\n\t}";
    }
    names += "\n\t\t'" + fileName.toLowerCase() + "'" + spacing + " => '" + source.icons[i].title.replace(/&amp;/g, "&").replace(/'/g, "\\'") + "',";
}
sass += "\n}"
min += "\n}"
names += "\n\t);\n}\n"

// Generate Sass file with color variables
fs.writeFile("./sass/_simple-icons.scss", sass, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The Sass file was built");
});

fs.writeFile("./sass/_simple-icons-min.scss", min, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The Minimal Sass file was built");
});

// Generate PHP file with names
fs.writeFile("./includes/simple-icons.php", names, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The PHP file was built");
});

// Custom icons that should not be deleted (not from Simple Icons or modified versions)
const customIcons = [
    'audio-mute', 'book', 'checkmark', 'circle', 'cog', 'eraser', 'fullscreen',
    'home', 'info', 'mail', 'notice', 'phone', 'reply', 'search', 'summary',
    'user', 'website',
    // Legacy icons that were in Simple Icons but removed/renamed
    'twitter', 'nostr', 'googlepodcasts'
];

// Copy curated SVG files from Simple Icons to static/svg
var svgDir = path.join(__dirname, '..', 'svg');
var simpleIconsDir = path.join(__dirname, '..', '..', 'node_modules', 'simple-icons', 'icons');

// Get list of existing SVG files
fs.readdir(svgDir, function(err, files) {
    if (err) {
        console.log('Error reading svg directory:', err);
        return;
    }

    // Remove non-custom SVG files
    var removed = 0;
    files.forEach(function(file) {
        if (file.endsWith('.svg')) {
            var iconName = file.replace('.svg', '');
            // Keep custom icons, remove everything else
            if (!customIcons.includes(iconName)) {
                var filePath = path.join(svgDir, file);
                try {
                    fs.unlinkSync(filePath);
                    removed++;
                } catch (err) {
                    console.error('Error deleting SVG file "' + filePath + '":', err && err.message ? err.message : err);
                }
            }
        }
    });
    console.log('Removed ' + removed + ' old SVG files');

    // Copy curated icons from Simple Icons
    var copied = 0;
    curatedIcons.forEach(function(iconName) {
        var srcFile = path.join(simpleIconsDir, iconName + '.svg');
        var destFile = path.join(svgDir, iconName + '.svg');

        if (fs.existsSync(srcFile)) {
            // The Icons API sanitizer strips <title> tags but keeps their text,
            // so remove them here to keep the markup clean on WordPress 7.1+.
            var svgContent = fs.readFileSync(srcFile, 'utf8').replace(/<title>[^<]*<\/title>/g, '');
            fs.writeFileSync(destFile, svgContent);
            copied++;
        } else {
            console.log(
                'Warning: Icon "' + iconName + '" not found in Simple Icons. ' +
                'If this is a custom icon, add it to the customIcons array.'
            );
        }
    });
    console.log('Copied ' + copied + ' curated SVG files from Simple Icons');
});
