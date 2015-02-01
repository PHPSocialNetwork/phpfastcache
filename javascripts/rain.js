
// Scroll to the bottom to see the entry point.

// Classes:
// - Generator: singleton used for making things (random ints for example).
// - Vector: very simple 2d vector class.
// - Cursor: manages the mouse position and interaction with particles.
// - Cloud: generates the cloud of letters.
// - RainDrop: maintains the state of a single particle in motion.
// - Rain: The controller of the rain drops...
// - Simulation: actually holds the run loop and ties together rain controls.

(function(document){

var ExperimentSupported = false;

(function(){

// Doing a bit of browser detection:
// I need to know which browser prefix as well
// whether or not to use 3d translations.
var transform_property,
    supports_3d;

(function(){
    var styles = document.createElement('div').style;
    
    var test_properties = function(properties){
        return properties.filter(function(prop){
            return (styles[prop] !== undefined);
        })[0];
    };
    
    transform_property = test_properties([
        'transform',
        'WebkitTransform',
        'MozTransform',
        'msTransform',
        'OTransform'
    ]);
    
    supports_3d = test_properties([
        'perspectiveProperty',
        'WebkitPerspective',
        'MozPerspective',
        'msPerspective',
        'OPerspective'
    ]);
})();

ExperimentSupported = !!transform_property;

// Extend a few natives to help me out:
var helpers = {
    round: function(number){
        return Math.round(number * 10) / 10;
    },
    translate3d: function(vector){
        return 'translate3d(' + (vector.x | 0) + 'px,' + (vector.y | 0)  + 'px, 0px)';
    },
    translate2d: function(vector){
        return 'translate(' + (vector.x | 0) + 'px,' + (vector.y | 0)  + 'px)';
    },
    rotate: function(value){
        return 'rotate(' + (value | 0) + 'deg)';
    }
};

helpers.translate = (supports_3d) ? helpers.translate3d : helpers.translate2d;

// Theses methods will be applied to HTMLElements
var methods = {
    transform: function(translation, rotation){
        translation = helpers.translate(translation);
        rotation = rotation ? helpers.rotate(rotation) : '';
        
        this.style[transform_property] = translation + ' ' + rotation;
        
        return this;
    },
    
    opacity: function(value){
        this.style.opacity = value;
        return this;
    },
    
    position: function(position){
        this.style.left = position.x + 'px';
        this.style.top = position.y + 'px';
        return this;
    },
    
    color: function(color){
        this.style.color = color;
        return this;
    },
    
    show: function(){
        this.style.display = '';
        return this;
    },
    
    hide: function(){
        this.style.display = 'none';
        return this;
    },
    
    search: function(selector){
        return this.querySelectorAll(selector);
    },
    
    find: function(selector){
        return this.querySelector(selector);
    }
};

// Add just a few helpers to the document object.
document.id = document.getElementById;
document.search = methods.search;
document.find = methods.find;

// Actually apply methods to HTMLElement.
for (var method in methods)
    HTMLElement.prototype[method] = methods[method];

Array.prototype.random = function(){
    return this[Math.floor(Math.random() * this.length)];
};

// Implement a super simple version of bind, if not already there.
if (!Function.prototype.bind){
    Function.prototype.bind = function (bind){
        var self = this;
        return function(){
            return self.apply(bind, arguments);
        };
    };
}

})(); // Finished extending prototypes.

// Super simple 2d vector class.
var Vector = function(x, y){
    this.x = x || 0;
    this.y = y || 0;
};

Vector.random = function(min, max){
    return new Vector(Generator.random(min, max), Generator.random(min, max));
};

Vector.prototype = {
    
    add: function(vector){
        return new Vector(this.x + vector.x, this.y + vector.y);
    },
    
    subtract: function(vector){
        return new Vector(this.x - vector.x, this.y - vector.y);
    },
    
    scale: function(value){
        return new Vector(this.x * value, this.y * value);
    },
    
    multiply: function(vector){
        return new Vector(this.x * vector.x, this.y * vector.y);
    },
    
    divide: function(vector){
        return new Vector(this.x / vector.x, this.y / vector.y);
    },
    
    length: function(){
        return Math.sqrt(this.x * this.x + this.y * this.y);
    },
    
    toString: function(){
        return ['[',this.x, ',', this.y, ']'].join('');
    }
    
};

var Generator = {
    
    letters: '!\"\',.0123456789?ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split(''),
    
    random: function(min, max){
        min = min || 0;
        max = max || 1;
        return Math.random() * (max - min) + min;
    },
    
    randomInt: function(min, max){
        return Math.floor(this.random(min, max));
    },
    
    randomLetter: function(color){
        var element = document.createElement('div');
        element.innerHTML = this.letters[this.randomInt(0, this.letters.length)];
        return element.color(color);
    },
    
    bool: function(bias){
        return !!Math.round(this.random() + (bias || 0));
    },
    
    // Generates an array of hex colors based on two inputs.
    gradient: function(stop1, stop2){
        var regex = /#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})/i,
            steps, diff, i, j;
        
        // convert stops to integer values.
        var stops = [stop1, stop2].map(function(stop){
            return regex.exec(stop).slice(1).map(function(color){
                return parseInt(color, 16);
            });
        });
        
        for (diff = i = 0; i < 3; i++)
            diff = Math.max(diff, Math.abs(stops[0][i] - stops[1][i]));
        
        for (steps = [], i = 0; i < diff + 1; i++){
            var step = [];
            
            for (j = 0; j < 3; j++){
                var value = stops[0][j] + (i * ((stops[1][j] - stops[0][j]) / diff));
                value = Math.round(value).toString(16);
                step.push(value.length < 2 ? '0' + value : value);
            }
            
            steps.push('#' + step.join(''));
        }
        
        return steps;
    }
    
};

var Cursor = function(element, container){
    this.element = element;
    this.container = container;
    
    this.radius = 0.18;
    
    this.offset = this.calcOffset();
    
    // initialize the cursor to be off the screen.
    this.move({
        pageX: -100,
        pageY: -100
    });
    
    document.addEventListener('mousemove', this.move.bind(this), false);
};

Cursor.prototype = {
    
    // the offset is used to center the cursor div on the actual mouse.
    calcOffset: function(){
        return new Vector(this.element.clientWidth, this.element.clientHeight).scale(0.5);
    },
    
    // called when ever the mouse moves.
    move: function(event){
        var container = this.container,
            position = new Vector(event.pageX, event.pageY);
        
        // calculate a normalize position relative the the rain container.
        this.scaled = position.subtract(container.position).divide(container.size);
        
        // actually move the cursor element.
        this.element.position(position.subtract(this.offset));
    },
    
    // in the run loop, every particle gets passed here.
    adjustParticle: function(particle){
        var position = this.scaled,
            diff = particle.current.subtract(position),
            distance = diff.length();
        
        if (distance < this.radius)
            particle.current = position.add(diff.scale(this.radius / distance));
    }
    
};

var Cloud = function(container){
    this.container = container;
    this.colors = Generator.gradient('#999999', '#cccccc');
    this.puff_radius = 45;
    this.puff_particles = 25;
    this.centers = [
        [ 25, 120], [ 85, 120], [145, 125], [195, 125],
        [250, 120], [320, 120], [380, 125], [440, 120],
        [ 80,  80], [145,  70], [220,  70], [280,  70],
        [360,  80], [200,  45], [280,  50]
    ];
    
    this.generate();
};

Cloud.prototype = {
    
    generate: function(){
        var fragment = document.createDocumentFragment(),
            centers = this.centers;
        
        centers.forEach(function(center){
            center = new Vector(center[0], center[1]);
            fragment.appendChild(this.generatePuff(center));
        }, this);
        
        this.container.appendChild(fragment);
    },
    
    generatePuff: function(center){
        var element = document.createElement('div'),
            radius = this.puff_radius,
            count = this.puff_particles;
        
        while (count--){
            var rotation = Generator.randomInt(0, 360),
                postion;
            
            // make sure we generate a div within the radius.
            do {
                position = Vector.random(-radius, radius);
            } while(position.length() > radius);
            
            element.appendChild(this.generateParticle(position, rotation));
        }
        
        return element.position(center);
    },
    
    generateParticle: function(position, rotation){
        var letter = Generator.randomLetter(this.colors.random());
        return letter.transform(position, rotation);
    }
    
};

var RainDrop = function(element, container, current, previous){
    this.element = element;
    this.container = container;
    this.current = current;
    this.previous = previous;
    
    this.rotation = Generator.randomInt(0, 360);
    this.rotation_step = Generator.randomInt(-10, 10);
    
    this.style();
    
    container.appendChild(element);
};

RainDrop.prototype = {
    
    // this is called when a drop falls off the page.
    // just hides the element, reset its position to something random.
    reset: function(position){
        var element = this.element.hide();
        this.previous = this.current = position;
    },
    
    update: function(acceleration){
        var temp = this.current;
        
        // simple parabolic motion equation.
        this.current = this.current.scale(2).subtract(this.previous).add(acceleration);
        this.previous = temp;
        
        this.rotation += this.rotation_step;
        
        this.style();
        
        return this;
    },
    
    remove: function(){
        this.container.removeChild(this.element);
    },
    
    style: function(){
        var element = this.element,
            current = this.current,
            location = current.multiply(this.container.size);
        
        element.opacity(Math.max(0, Math.min(1, 1.5 - current.y)));
        element.transform(location, this.rotation).show();
    },
    
    visible: function(){
        return this.current.y < 1.5;
    }
    
};

var Rain = function(container, cursor){
    this.container = container;
    this.cursor = cursor;
    this.colors = Generator.gradient('#6795B5', '#aaaaaa');
    this.gravity = this.base_gravity = new Vector(0, 0.00075);
    this.drop_limit = this.base_drop_limit = 75;
    this.drops = [];
};

Rain.prototype = {
    
    // called in the run loop.
    update: function(){
        this.adjustCount();
        
        var drops = this.drops,
            limit = drops.length,
            drop;
        
        for (var i = 0; i < limit; i++){
            drop = drops[i].update(this.gravity);
            if (!drop.visible()) drop.reset(new Vector(Generator.random()));
            else this.cursor.adjustParticle(drop);
        }
        
        return limit;
    },
    
    adjustCount: function(){
        var drops = this.drops,
            length = drops.length,
            limit = this.drop_limit;
        
        if (length < limit && Generator.bool(0.25))
            drops.push(this.createDrop());
        else if (drops.length > limit)
            drops.pop().remove();
    },
    
    createDrop: function(){
        var current = new Vector(Generator.random()),
            previous = current.add(Vector.random(-0.0075, 0.0075)),
            element = Generator.randomLetter(this.colors.random());
        
        return new RainDrop(element, this.container, current, previous);
    },
    
    changeCount: function(value){
        return (this.drop_limit = Math.floor(this.base_drop_limit * value));
    },
    
    changeGravity: function(value){
        this.gravity = this.base_gravity.scale(value);
    },
    
    changeFont: function(value){
        this.container.style.fontSize = Math.round(value * 100) + '%';
    }
    
};

var Simulation = function(controls){
    var container = this.container = document.id('rain'),
        cursor = document.id('cursor'),
        cloud = document.id('cloud');
    
    this.controls = controls;
    
    window.addEventListener('resize', this.resize.bind(this), false);
    this.resize(); // cache the containers position and dimensions.
    
    this.rain = new Rain(container, new Cursor(cursor, container));
    this.cloud = new Cloud(cloud);
    
    this.initControls();
    this.run();
};

Simulation.prototype = {
    
    resize: function(){
        var container = this.container;
        container.size = new Vector(container.clientWidth, container.clientHeight);
        container.position = new Vector(container.offsetLeft, container.offsetTop);
    },
    
    initControls: function(){
        var controls = this.controls,
            rain = this.rain;
        
        this.drop_counter = document.find('p.drop-counter span');
        
        var map = [{
            input: controls.find('*[name="gravity"]'),
            method: 'changeGravity'
        }, {
            input: controls.find('*[name="drops"]'),
            method: 'changeCount'
        }, {
            input: controls.find('*[name="font-size"]'),
            method: 'changeFont'
        }];
        
        map.forEach(function(control){
            control.input.addEventListener('change', function(){
                rain[control.method](control.input.value);
            }, false);
        });
    },
    
    run: function(){
        var counter = this.drop_counter,
            rain = this.rain,
            time = +new Date;
        
        (function loop(){
            var current = +new Date;
            
            if ((current - time) > 25){ // cap at 25 ms/frame
                counter.innerHTML = rain.update();
                time = current;
            }
            
            requestAnimFrame(loop);
        })();
    }
    
};

document.addEventListener('DOMContentLoaded', function(){
    
    var controls = document.find('div.controls'),
        hidden = document.find('div.controls.hidden'),
        input = document.createElement('input');
    
    input.setAttribute('type', 'range');
    
    if (input.type != 'range'){
        // hide the current controls;
        controls.className += ' hidden';
        // reveal the alternate controls
        if(typeof hidden != "undefined" && hidden != null ) {
            hidden.className = hidden.className.replace('hidden', '');
            controls = hidden;
        }

        // reasign the controls to the hidden ones

    }
    
    if (ExperimentSupported) new Simulation(controls);
    else document.id('screen').className += ' not-supported';
    
}, false);

})(document);
