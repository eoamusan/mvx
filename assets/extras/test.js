// you can write to stdout for debugging purposes, e.g.
// console.log('this is a debug message');

function solution(A) {
    let positives = Array.from({ length: 100000 }, (_, i) => 1 + i);
    
    for(var j = 0; j < positives.length; j++){
        if(A.indexOf(positives[j]) == -1){
            return positives[j];
        }
    }
}

let A = [0, 1, 2, 3];
let B = [1, 2, 3];
let C = [-5, 0, 2, 3];
let D = [0, 5, 2, 3];
console.log(solution(A));
console.log(solution(B));
console.log(solution(C));
console.log(solution(D));