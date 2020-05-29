
const fs = require('fs');
const Ajv = require('ajv');

const json = JSON.parse(fs.readFileSync('langtags.json'));

var ajv = new Ajv();
var validate = ajv.compile(JSON.parse(fs.readFileSync('langtags.schema.json')));
var valid = validate(json);

if(!valid) {
  validate.errors.forEach(e => {
    console.log(e);
    if(e.dataPath)
      console.log(eval('json'+ e.dataPath));
    console.log('');
  });
}
