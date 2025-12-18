let devices = load('devices') || [];
function addDevice(){
  devices.push(deviceName.value);
  save('devices', devices);
  render();
}
function render(){
  deviceList.innerHTML = devices.map(d => '<li>'+d+'</li>').join('');
}
render();