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

const icons = [ "twitter", "swarm", "facebook", "instagram", "microdotblog", "bluesky", "github", "flickr", "mastodon", "wordpress", "tumblr", "blogger", "medium", "reddit" ];

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
// Use function from SimpleIcons Util file

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
names += "<?php\n\nfunction simpleicons_iw_get_names() {\n\treturn array(";
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
    if  ( icons.includes( fileName.toLowerCase() ) ) {
	min += "\n\t.svg-" + fileName.toLowerCase() + spacing + "{" + "\n\t\tcolor: #" + source.icons[i].hex.toUpperCase() + ";" + "\n\t}";
    }
    names += "\n\t\t'" + fileName.toLowerCase() + "'" + spacing + "=>" + spacing + "'" + source.icons[i].title.replace(/&amp;/g, "&").replace("'", /&apos;/g ) + "',";
}
sass += "\n}"
min += "\n}"
names += "\n\t);\n}"

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
