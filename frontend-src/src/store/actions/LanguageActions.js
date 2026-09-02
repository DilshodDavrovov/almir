export const CHANGE_LANGUAGE_ACTION = 'change-language-action';

export function handleLanguageAction(lang) {
    return {
        type: CHANGE_LANGUAGE_ACTION,
        payload: lang,
    };
}