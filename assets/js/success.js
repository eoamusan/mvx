function successAnimation() {
    var canvas = Snap.select("#svg-animation"),
        allItems = canvas.select("#all-items"),

        envelopeBg = canvas.select("#envelope-bg"),
        envelopeFg = canvas.select("#envelope-fg"),
        envelopeLip = canvas.select("#envelope-lip"),

        screens = canvas.select("#screens"),
        screen1 = screens.select("#screen1"),
        screen2 = screens.select("#screen2"),
        screen3 = screens.select("#screen3"),

        circleBg = canvas.select("#bg-circle"),
        bigCircle = canvas.circle(177, 177, 177),

        screenlist = [screen1, screen2, screen3];

    var envelopeLipTop = allItems.polygon("265,116 176,56 87,116");
    var closedLip = "265,116 176,176 87,116";

    allItems.attr({
        clip: bigCircle,
        opacity: 1
    });

    envelopeLipTop.attr({
        opacity: 0
    });

    screens.transform("t0,280");
    envelopeTransform("t0,320", false);

    function envelopeTransform(params, animation, nextAnim) {
        if (!animation) {
            envelopeBg.transform(params);
            envelopeFg.transform(params);
            envelopeLip.transform(params);
        } else {
            envelopeBg.animate({
                transform: params
            }, 320, mina.easeinout);
            envelopeFg.animate({
                transform: params
            }, 320, mina.easeinout);
            envelopeLip.animate({
                transform: params
            }, 320, mina.easeinout, nextAnim);
        }
    }

    function animateUp(element) {
        element.animate({
            transform: "t0,-280"
        }, 1000, mina.elastic);
    }

    for (i = 0; i < 3; i++) {
        (function(i) {
            setTimeout(function() {
                animateUp(screenlist[i]);
            }, 200 * i);
        }(i));
    }

    function initSequence() {
        screen1.animate({
            transform: "t-59,-243"
        }, 200, mina.easeinout);
        screen3.animate({
            transform: "t59,-319"
        }, 200, mina.easeinout, dropPulse);
    }

    function dropPulse() {
        pulseCircle = canvas.circle(176, 176, 50);

        pulseCircle.attr({
            stroke: "#fff",
            fill: "rgba(0,0,0,0)",
            strokeWidth: 15,
            opacity: 0
        });

        pulseCircle.animate({
            r: 100,
            opacity: 1
        }, 200, mina.easeinout);

        setTimeout(function() {
            pulseCircle.animate({
                r: 120,
                opacity: 0
            }, 200, mina.easeinout, moveEnvelope);
        }, 90);
    }

    function moveEnvelope() {
        setTimeout(function() {
            envelopeTransform("t0,0", true, animateLip);
        }, 50)
    }

    function animateLip() {
        envelopeLip.animate({
            opacity: 0
        }, 0, mina.linear);

        envelopeLipTop.attr({
            fill: "#A0AFB5",
            stroke: "#737F84",
            strokeWidth: 2,
            strokeLineJoin: "round",
            opacity: 1
        });

        envelopeLipTop.animate({
            "points": closedLip,
            fill: "#fff"
        }, 200, mina.easeinout, envelopeAnimOut);
    }

    function envelopeAnimOut() {
        setTimeout(function() {
            envelopeBg.animate({
                transform: "t320,0"
            }, 320, mina.easeinout);

            envelopeFg.animate({
                transform: "t320,0"
            }, 320, mina.easeinout);

            envelopeLipTop.animate({
                transform: "t320,0"
            }, 320, mina.easeinout);

            screens.animate({
                transform: "t320,280"
            }, 320, mina.easeinout, closeCircle);
        }, 200)
    }

    function closeCircle() {
        circleBg.animate({
            r: 95,
            fill: '#4ad3ac'
        }, 800, mina.bounce);

        tick = allItems.polyline("156,179 170,193 199.2,163.8");
        tick.attr({
            stroke: "#fff",
            fill: "rgba(0,0,0,0)",
            strokeWidth: 5
        });
    }

    function animate() {
        initSequence();
    }

    setTimeout(function() {
        animate();
        canvas.unclick();
    }, 90);
}