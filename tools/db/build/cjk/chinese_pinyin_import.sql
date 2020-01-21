        load data local infile 'C:/Projects/keyman/sites/api.keyman.com/tools/db/build/cjk/chinese_pinyin.txt'
          into table kmw_chinese_pinyin
          character set utf8
          lines terminated by '\r\n'
          ignore 1 lines;