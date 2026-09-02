export const LOGOUT_ACTION = '[Logout action] logout action';

export function logout(history) {
    sessionStorage.removeItem('token');
    history.push('/login');
    return {
        type: LOGOUT_ACTION,
    };
}