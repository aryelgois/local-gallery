/**
 * LocalGallery
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/local-gallery
 *
 * @param object ns namespace
 * @param object $  jQuery
 */
(function LocalGallery(ns, $) {
  /**
   * Extracts the basename of a given path
   *
   * @author Eugen Mihailescu
   * @link https://stackoverflow.com/a/29939805
   *
   * @param string str A path
   * @param string sep Directory separator
   *
   * @return string
   */
  const basename = function pathBasename(str, sep) {
    return str.substr(str.lastIndexOf(sep) + 1);
  };

  const dirname = function pathDirname(str, sep) {
    return str.substr(0, str.lastIndexOf(sep));
  };

  const has = Object.prototype.hasOwnProperty;


  const storage = {
    index: {},
    indexes: {},
  };


  const f = {
    events: {
      select_album (event) {
        let album = $(this).data('album');
        let album_encoded = encodeURI(album);
        let $body = $('body').addClass('show_aside');
        let $main = $body.children('main');
        let $section = $main.children(`section[data-album="${album_encoded}"]`);

        if ($section.length === 0) {
          $section = $('<section class="gallery">')
            .on('click', 'div', f.events.select_file)
            .attr('data-album', album_encoded);
          f.gallery.fill(f.gallery.list_album(album), $section);
          $main.append($section);
        }

        $section.show().siblings().hide();
      },

      select_file (event) {
        // TODO
        console.log($(this).data());
      },
    },

    gallery: {
      fill (list, container) {
        $.each(list, (i, v) => {
          let path = encodeURI(v.file.album + '/' + v.file.name);
          let $el = $('<div>')
            .css({'background-image': `url("/storage/${path}")`});

          if (has.call(v, 'attr')) {
            $el.attr(v.attr);
          }
          if (has.call(v, 'data')) {
            $el.data(v.data);
          }
          if (has.call(v, 'text')) {
            $el.append($('<span>').text(v.text));
          }

          container.append($el);
        })
      },

      list_albums () {
        let list = [];
        $.each(storage.index, (album, files) => {
          list.push({
            file: Object.values(files)[0],
            text: basename(album) || 'No Album',
            data: {album},
          });
        });
        return list;
      },

      list_album (album) {
        let list = [];
        $.each(storage.index[album], (file, data) => {
          list.push({
            file: data,
            data: {file: data},
          });
        });
        return list;
      },
    },

    index: {
      load (callback) {
        let stamp = {_: new Date().getTime()};
        $.getJSON('/storage/index.json', stamp, (albums) => {
          f.index.update(albums);
          callback.call();
        }).fail(() => {
          console.error('Could not load index.json');
        });
      },

      update (albums) {
        let indexes = {
          favorite: [],
          mtime: [],
          size: [],
          tags: {},
        };

        $.each(albums, (album, files) => {
          $.each(files, (file, data) => {
            data.album = album;
            data.name = file;

            if (has.call(data, 'favorite') && data.favorite === true) {
              indexes.favorite.push(data);
            }

            $.each(data.tags, (i, tag) => {
              indexes.tags[tag] = indexes.tags[tag] || [];
              indexes.tags[tag].push(data);
            });

            indexes.mtime.push(data);
            indexes.size.push(data);
          });
        });

        // usort indexes.{mtime,size}

        storage.index = albums;
        storage.indexes = indexes;
      },
    },
  };


  ns.init = function localGalleryInit() {
    f.index.load(() => {
      let albums = f.gallery.list_albums();
      let $aside = $('<nav class="gallery">')
        .on('click', 'div:not(.add-item)', f.events.select_album);
      let $main = $('<section class="gallery">')
        .on('click', 'div', f.events.select_album)
        .attr('data-album', '.');

      f.gallery.fill(albums, $aside.add($main));

      $('<div class="add-item"><i>+</i></div>').appendTo($aside);

      $('body > aside').empty().append($aside);
      $('body > main').empty().append($main);
    });
  };
  ns.storage = storage;
}(window.app = window.app || {}, jQuery));
