// Generate SASS file from Simple Icons json data
// Get JSON from source file
var source = require('../../node_modules/simple-icons/_data/simple-icons.json');

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
var names = "";
var textdomain = "indieweb";
sass += ".relme li a {\n";
names += "<?php\n\nfunction simpleicons_iw_get_names() {\n\treturn array(";
var maxNameLength = 0;

for (var i = 0; i < source.icons.length; i++) {
    	var fileName = source.icons[i].title.toLowerCase();
	fileName = fileName.replace(/\+/g, "plus").replace(/\./g, "dot").replace(/&/g, "and").replace(/đ/g, "d").replace(/ħ/g, "h").replace(/ı/g, "i").replace(/ĸ/g, "k").replace(/ŀ/g, "l").replace(/ł/g, "l").replace(/ß/g, "ss").replace(/ŧ/g, "t").normalize("NFD").replace(/[^a-z0-9]/g, "");
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
    var fileName = source.icons[i].title.toLowerCase();
	fileName = fileName.replace(/\+/g, "plus").replace(/\./g, "dot").replace(/&/g, "and").replace(/đ/g, "d").replace(/ħ/g, "h").replace(/ı/g, "i").replace(/ĸ/g, "k").replace(/ŀ/g, "l").replace(/ł/g, "l").replace(/ß/g, "ss").replace(/ŧ/g, "t").normalize("NFD").replace(/[^a-z0-9]/g, "");
    spacing = "";
    if (fileName.length < maxNameLength) {
        spacing = " ".repeat(maxNameLength - fileName.length);
    }

    sass += "\n\t.svg-" + fileName.toLowerCase() + spacing + "{" + "\n\t\tcolor: #" + source.icons[i].hex.toUpperCase() + ";" + "\n\t}";
    names += "\n\t\t'" + fileName.toLowerCase() + "'" + spacing + "=>" + spacing + "'" + source.icons[i].title.replace(/&amp;/g, "&").replace("'", /&apos;/g ) + "',";
}
sass += "\n}"
names += "\n\t);\n}"

// Generate Sass file with color variables
fs.writeFile("./sass/_simple-icons.scss", sass, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The Sass file was built");
});

// Generate PHP file with names
fs.writeFile("./includes/simple-icons.php", names, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The PHP file was built");
});
