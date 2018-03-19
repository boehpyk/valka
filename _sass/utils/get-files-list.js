/**
* Returns all files from directory by extantion.
* @example
* export default getFilesList(require.context('./', true, /\.svg$/), 'svg');
*/
export function getFilesList(r, type) {
  const regexp = new RegExp('\\.\\/(.*)\\.' + type);
  return r.keys().reduce((container, path) => {
    const key = path.replace(regexp, '$1');
    const value = r(path);
    container[key] = value;
    return container;
  }, {});
}