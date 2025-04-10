import React from "react";
import { ToastContainer, toast as toastify } from "react-toastify";
import { Toast } from "./Toast";
import "react-toastify/dist/ReactToastify.css";

// Custom toast functions
export const toast = {
  success: (message) => {
    toastify.success(<Toast type="success" message={message} />, {
      className: "toast-success",
      // autoClose: 2000,
      closeOnClick : true,
    });
  },
  error: (message) => {
    toastify.error(<Toast type="error" message={message} />, {
      className: "toast-error",
      // autoClose: 2000,
      closeOnClick : true,
    });
  },
  info: (message) => {
    toastify.info(<Toast type="info" message={message} />, {
      className: "toast-info",
      // autoClose: 2000,
      closeOnClick : true,
    });
  },
  warning: (message) => {
    toastify.warning(<Toast type="warning" message={message} />, {
      className: "toast-warning",
      // autoClose: 2000,
      closeOnClick : true,
    });
  }
};

export const NotificationProvider = ({ children }) => {
  return (
    <>
      {children}
      <ToastContainer
        position="top-right"
        autoClose={2000}
        hideProgressBar={true}
        newestOnTop
        closeOnClick={true}
        rtl={false}
        pauseOnHover
        // pauseOnFocusLoss
        // draggable
      />
    </>
  );
};
