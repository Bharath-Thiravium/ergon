// Optimized JavaScript - Essential functions only
(function(){
'use strict';

// Lazy load images
const lazyImages=document.querySelectorAll('img[data-src]');
if('IntersectionObserver' in window){
const imageObserver=new IntersectionObserver((entries)=>{
entries.forEach(entry=>{
if(entry.isIntersecting){
const img=entry.target;
img.src=img.dataset.src;
img.classList.remove('lazy');
imageObserver.unobserve(img);
}});
});
lazyImages.forEach(img=>imageObserver.observe(img));
}

// Debounced search
function debounce(func,wait){
let timeout;
return function executedFunction(...args){
const later=()=>{
clearTimeout(timeout);
func(...args);
};
clearTimeout(timeout);
timeout=setTimeout(later,wait);
};
}

// Fast DOM ready
function ready(fn){
if(document.readyState!=='loading'){
fn();
}else{
document.addEventListener('DOMContentLoaded',fn);
}
}

// Efficient AJAX
function ajax(url,options={}){
return fetch(url,{
method:options.method||'GET',
headers:{'Content-Type':'application/json',...options.headers},
body:options.body?JSON.stringify(options.body):null
}).then(r=>r.json());
}

// Export globals
window.debounce=debounce;
window.ready=ready;
window.ajax=ajax;

})();