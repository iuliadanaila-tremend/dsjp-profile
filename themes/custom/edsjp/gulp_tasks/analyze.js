var gulp = require('gulp'),
    wait = require('gulp-wait'),
    analyzeCss = require('gulp-analyze-css'),
    through = require('through2'),
    notify = require('gulp-notify');

var str = '',
    nav = '',
    metrics = '';

function jsonPrinter(non_nested_obj) {

    var offenders = non_nested_obj.offenders;
    var objMetrics= non_nested_obj.metrics;

    for (let key in offenders) {
        if (offenders.hasOwnProperty(key)) {
            nav += "<li><a href='#" + key + "'>" + key + "</a></li>";
            str += "<div class=\"card\" id='" + key + "'><div class=\"card-header\"><p class=\"card-header-title\">" + key + "</p></div>";
            str += "<div class=\"card-content\"><div class=\"content\">";

            if (Array.isArray(offenders[key])) {
                str += "<ul>";
                offenders[key].forEach(function(el) {
                    str += "<li>" + el + "</li>";
                });
                str += "</ul>";
            } else {
                str += offenders[key];
            }
            str += "</div></div></div><br/>";
        }
    }

    metrics += "<ul>";

    for (let key in objMetrics) {

        if (objMetrics.hasOwnProperty(key)) {
            metrics += "<li><span style='display:inline-block;min-width:25%;'>" + key + ":</span> " + objMetrics[key] + "</li>";
        }
    }

    metrics += "</ul>";

    var style = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.4/css/bulma.min.css">';
    var html = '<!doctype html>' +
        '<html>' +
        '<head>' +
        '<meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' +
        style +
        '<title>CSS Report</title>' +
        '</head>' +
        '<body>' +
        '<div id="app">'  +
        '<section class="main-content columns is-fullheight">'  +
        '<aside class="column is-2 is-narrow-mobile is-fullheight section is-hidden-mobile">'  +
        '<div style="position: fixed;">' +
        '<h1><strong>' +
        non_nested_obj.generator +
        '</strong></h1>' +
        '<p class="menu-label">Metrics</p>'  +
        '<ul class="menu-list">'  +
        '<li><a href="#metrics">Metrics</a></li>' +
        '</ul>  '  +
        '<p class="menu-label">CSS Report Offenders</p> '  +
        '<ul class="menu-list"> '  +
        nav  +
        '</ul>  '  +
        '</div>' +
        '</aside>  '  +
        '<div class="container column is-10">'  +
        '<div class="section">'  +
        '<div class="card" id="metrics">' +
        '<div class="card-header"><p class="card-header-title">Metrics</p></div>'  +
        '<div class="card-content"><div class="content">' +
        metrics +
        '</div></div></div><br/>' +
        str +
        '</div>'  +
        '</div>'  +
        '</section>'  +
        '<footer class="footer is-hidden">'  +
        '<div class="container">'  +
        '<div class="content has-text-centered">'  +
        '<p>&copy;Big Bad Tremendous MCS</p>'  +
        '</div>'  +
        '</div>'  +
        '</footer>'  +
        '</div>'  +
        '</body>' +
        '</html>';

    return html;
}

var prettifyReport = () => {
    return through.obj((file, encoding, callback) => {
        var fileContents = file.contents;
    var htmlContents = jsonPrinter(JSON.parse(fileContents));
    file.contents = new Buffer(htmlContents);
    file.extname = '.html';
    return callback(null, file);
});
};

gulp.task('analyze-css', function (done) {
    gulp.src('./dist/css/main.min.css')
        .pipe(analyzeCss({outDiretory: './report'}))
        .pipe(notify({
            message: "json generated"
        }));
    done();
});

gulp.task('prettify-css-report', function (done) {
    gulp.src("./report/*.json")
        .pipe(prettifyReport())
        .pipe(gulp.dest("./report/pretty/"));
    done();
});