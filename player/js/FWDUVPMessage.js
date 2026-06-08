/**
 * Message.
 *
 * @package fwdfs
 * @since fwdfs 1.0
 */

class FWDUVPMessage{


    /**
     * Constructor.
     */
    constructor() {
        this.textElement = document.createElement('div');
        this.textElement.className = 'fwdfs-message';
    }

    show(text, isError){
        if(this.isAnimating) return;
        
        this.textElement.innerHTML = text;
        document.documentElement.appendChild(this.textElement);
        
        this.textElement.style.position = 'fixed';
        this.textElement.style.zIndex = '99999999999';
        this.textElement.style.pointerEvents = 'none';
        this.textElement.style.top = '50%';
        this.textElement.style.left = '50%';
        this.textElement.style.transform = 'translate(-50%, -50%)';
        this.textElement.style.padding = '10px 16px';
        this.textElement.style.borderRadius = '4px';
        this.textElement.style.color = '#000000';
        this.textElement.style.fontSize = '13px';
        this.textElement.style.fontWeight = '600';
        this.textElement.style.backgroundColor = isError ? '#D63638' : '#7AD03A';
        this.textElement.style.opacity = 0;

        if(typeof FWDAnimation === 'undefined' || typeof Expo === 'undefined'){
            this.textElement.style.transition = 'opacity 200ms ease';
            this.textElement.style.opacity = 1;

            this.isAnimating = true;
            setTimeout(() => {
                this.textElement.style.opacity = 0;
                setTimeout(() => {
                    this.isAnimating = false;
                }, 220);
            }, 1800);
            return;
        }

        FWDAnimation.killTweensOf(this.textElement);
        FWDAnimation.to(this.textElement, .1, {opacity:0, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .1, {opacity:1, backgroundColor:isError ? '#D63638' : '#7AD03A', delay:.1, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .1, {opacity:0, delay:.2, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .1, {opacity:1, delay:.6, delay:.3, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .1, {opacity:0, delay:.4, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .1, {opacity:1, delay:.5, ease:Expo.easeOut});
        FWDAnimation.to(this.textElement, .8, {opacity:0, delay:2,  ease:Expo.easeOut, onComplete:() =>{
            this.isAnimating = false;
        }});

        this.isAnimating = true;
    }

}