function query_data(node){

var location = node.data.key.split('/');

if(location[0] == "events"){
	if(!location[1]){
		node.appendAjax({
			url: "json/events.json",
		});
	}
	else{
		events(node);
	}
}
if(location[0] == "locations"){
	if(!location[1]){
		node.appendAjax({
			url: "json/events.json",
		});
	}
	else{
		
	}
}
if(location[0] == "times"){
	if(!location[1]){
		node.appendAjax({
			url: "json/events.json",
		});
	}
	else{
		
	}
}


}

function events(node){

var location = node.data.key.split('/');

node.appendAjax({
	url: "get.php?method=meta&relation=" + location[1],
	title: "test",
});

}