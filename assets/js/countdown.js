function countdown(s){
    var seconds = document.querySelector('#seconds');
    var milliseconds = document.querySelector('#milliseconds');
 
    var ms = 1000;
    var i = 1;
 
    setDate();
 
    function setDate(){
 
        is_int(i);
        seconds.innerHTML = Math.floor(s);
 
        isZero(ms);
        milliseconds.innerHTML = ms;
 
        if(Math.floor(s) > 0){
            setTimeout(setDate,10);
        }
    }
 
    function is_int(value){
        if((parseFloat(value/100) == parseInt(value/100)) && !isNaN(value)){
            i++;
            s-= 1;
        } else {
            i++;
        }
    };
 
    function isZero(value){
        if(value == 0){
            ms = 1000;
        }
        else{
            ms -= 10;
        }
    };
}