import Plugin from 'src/script/plugin-system/plugin.class';
import DomAccess from 'src/script/helper/dom-access.helper';
import Iterator from 'src/script/helper/iterator.helper';

export default class RatingSystemPlugin extends Plugin {

    static options = {
        reviewPointAttr: 'data-review-form-point',
        ratingTextAttr: 'data-rating-text',

        activeClass: 'is-active',
        hiddenClass: 'd-none',
    };

    init() {
        this._ratingPoints = DomAccess.querySelectorAll(this.el, '[' + this.options.reviewPointAttr + ']');
        this._textWrappers = DomAccess.querySelectorAll(this.el, '[' + this.options.ratingTextAttr + ']', false);

        if (!this._ratingPoints) {
            return;
        }

        this._registerEvents();
    }

    _registerEvents() {
        Iterator.iterate(this._ratingPoints, point => {
            point.addEventListener('click', this._setRating.bind(this));
        });
    }

    /**
     * set icon class to display the current rating
     *
     * @param {Event} event
     *
     * @private
     */
    _setRating(event){
        Iterator.iterate(this._ratingPoints, radio => {
            const radioValue = radio.getAttribute(this.options.reviewPointAttr);
            const targetValue = event.currentTarget.getAttribute(this.options.reviewPointAttr);

            if (radioValue <= targetValue) {
                radio.classList.add(this.options.activeClass);

            } else {
                radio.classList.remove(this.options.activeClass);
            }

            radio.addEventListener('click', this._showInfoText.bind(this));
        });
    }

    /**
     * show info text for current rating
     *
     * @param {Event} event
     *
     * @private
     */
    _showInfoText(event) {
        const targetValue = event.target.value;

        Iterator.iterate(this._textWrappers, textWrapper => {
            if (textWrapper.hasAttribute(`${this.options.ratingTextAttr}`)) {
                if (textWrapper.getAttribute(`${this.options.ratingTextAttr}`) === targetValue) {
                    textWrapper.classList.remove(this.options.hiddenClass);
                } else {
                    textWrapper.classList.add(this.options.hiddenClass);
                }
            }
        });
    }
}
