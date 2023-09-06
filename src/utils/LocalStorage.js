export const setItem = (key, value) => {
    try {
      const serializedValue = JSON.stringify(value);
      localStorage.setItem(key, serializedValue);
    } catch (error) {
      console.error(`Could not store item in local storage: ${error}`);
    }
  };
  
  export const getItem = (key, defaultValue = null) => {
    try {
      const serializedValue = localStorage.getItem(key);
      if (serializedValue === null) {
        return defaultValue;
      }  
      return JSON.parse(serializedValue);
    } catch (error) {
      console.error(`Could not retrieve item from local storage: ${error}`);
      return defaultValue;
    }
  };
  
  export const removeItem = (key) => {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      console.error(`Could not remove item from local storage: ${error}`);
    }
  };
  
  export const clearStorage = () => {
    try {
      localStorage.clear();
    } catch (error) {
      console.error(`Could not clear local storage: ${error}`);
    }
  };
  