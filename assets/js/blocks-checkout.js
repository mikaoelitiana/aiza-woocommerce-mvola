(function() {
    'use strict';
    
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { createElement } = window.wp.element;
    const { __ } = window.wp.i18n;
    const { decodeEntities } = window.wp.htmlEntities;
     const { useState, useEffect, useRef } = window.wp.element;

    const Label = (props) => {
        const { PaymentMethodLabel } = props.components;
        return createElement(PaymentMethodLabel, { 
            text: decodeEntities(props.title || __('MVola', 'woocommerce-mvola'))
        });
    };

    const Content = (props) => {
        const { eventRegistration, emitResponse } = props;
        const { onPaymentSetup } = eventRegistration;
        const [phoneNumber, setPhoneNumber] = useState('');
        const [validationError, setValidationError] = useState('');
        const phoneNumberRef = useRef(phoneNumber);
        phoneNumberRef.current = phoneNumber;
        
        useEffect(() => {
            const unsubscribe = onPaymentSetup(async () => {
                // Validate phone number
                const currentPhone = phoneNumberRef.current;
                const cleanPhone = currentPhone.replace(/[^0-9]/g, '');
                
                if (!currentPhone || cleanPhone.length < 10) {
                    setValidationError(__('Please enter a valid MVola phone number (at least 10 digits).', 'woocommerce-mvola'));
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: __('Please enter a valid MVola phone number.', 'woocommerce-mvola'),
                    };
                }
                
                setValidationError('');
                
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            mvola_phone_number: currentPhone,
                        },
                    },
                };
            });
            
            return unsubscribe;
        }, []);
        
        const description = props.description || __('Pay securely using MVola mobile money.', 'woocommerce-mvola');
        
        return createElement('div', { className: 'wc-block-components-payment-method-content' },
            createElement('p', null, decodeEntities(description)),
            createElement('div', { className: 'wc-block-components-text-input' },
                createElement('label', { htmlFor: 'mvola-phone-number' },
                    __('Your MVola Phone Number', 'woocommerce-mvola'),
                    createElement('abbr', { className: 'required', title: 'required' }, ' *')
                ),
                createElement('input', {
                    id: 'mvola-phone-number',
                    name: 'mvola_phone_number',
                    type: 'tel',
                    className: 'input-text',
                    placeholder: '034 00 000 00',
                    value: phoneNumber,
                    onChange: (e) => setPhoneNumber(e.target.value),
                    required: true,
                    'aria-describedby': validationError ? 'mvola-phone-error' : 'mvola-phone-help'
                }),
                validationError && createElement('div', { 
                    id: 'mvola-phone-error',
                    className: 'wc-block-components-validation-error',
                    role: 'alert'
                }, validationError),
                createElement('small', { id: 'mvola-phone-help' }, 
                    __('Enter your MVola account phone number', 'woocommerce-mvola')
                )
            )
        );
    };

    registerPaymentMethod({
        name: 'mvola',
        label: createElement(Label),
        content: createElement(Content),
        edit: createElement(Content),
        canMakePayment: () => true,
        ariaLabel: __('MVola', 'woocommerce-mvola'),
        supports: {
            features: ['products']
        }
    });
})();
