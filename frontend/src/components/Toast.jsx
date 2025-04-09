import React from "react";

export const Toast = ({ type, message }) => {
  // Toast configurations with GameXpress theme styling
  const toastConfig = {
    success: {
      bg: "bg-gradient-to-r from-indigo-600 to-indigo-500",
      iconBg: "bg-white",
      iconColor: "text-indigo-600",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
          <path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clipRule="evenodd" />
        </svg>
      )
    },
    error: {
      bg: "bg-gradient-to-r from-red-600 to-red-500",
      iconBg: "bg-white",
      iconColor: "text-red-600",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
          <path fillRule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clipRule="evenodd" />
        </svg>
      )
    },
    info: {
      bg: "bg-gradient-to-r from-blue-600 to-blue-500",
      iconBg: "bg-white",
      iconColor: "text-blue-600",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
          <path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
        </svg>
      )
    },
    warning: {
      bg: "bg-gradient-to-r from-amber-600 to-amber-500",
      iconBg: "bg-white",
      iconColor: "text-amber-600",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
          <path fillRule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
        </svg>
      )
    }
  };

  const config = toastConfig[type] || toastConfig.info;
  
  return (
    <div className="flex overflow-hidden rounded-lg shadow-lg">
      {/* Left colored stripe */}
      <div className={`${config.bg} w-2`}></div>
      
      {/* Main content */}
      <div className="bg-white dark:bg-gray-800 p-4 flex items-center w-full">
        {/* Icon container */}
        <div className={`flex-shrink-0 p-2 mr-3 rounded-full ${config.iconBg} ${config.iconColor} shadow-sm`}>
          {config.icon}
        </div>
        
        {/* Message */}
        <div className="flex-1">
          <p className="font-medium text-gray-800 dark:text-white">{message}</p>
        </div>
        
        {/* Close button */}
        <button className="ml-4 focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400 hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  );
};
