import { useState } from 'react';

const useNotification = () => {
  const [notification, setNotification] = useState(null);

  const clearNotification = () => {
    setNotification(null);
  }

  return [notification, setNotification, clearNotification];
};

export default useNotification;