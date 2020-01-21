        load data local infile 'C:/Projects/keyman/sites/api.keyman.com/tools/db/build/cjk/japanese.txt'
          into table kmw_japanese
          character set utf8
          lines terminated by '\r\n'
          ignore 1 lines;