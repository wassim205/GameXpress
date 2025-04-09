import React from "react";
import { ToastContainer, toast as toastify } from "react-toastify";
import { Toast } from "./Toast";
import "react-toastify/dist/ReactToastify.css";

// Custom toast functions
export const toast = {
  success: (message) => {
    toastify.success(<Toast type="success" message={message} />, {
      className: "toast-success"
    });
  },
  error: (message) => {
    toastify.error(<Toast type="error" message={message} />, {
      className: "toast-error"
    });
  },
  info: (message) => {
    toastify.info(<Toast type="info" message={message} />, {
      className: "toast-info"
    });
  },
  warning: (message) => {
    toastify.warning(<Toast type="warning" message={message} />, {
      className: "toast-warning"
    });
  }
};

export const NotificationProvider = ({ children }) => {
  return (
    <>
      {children}
      <ToastContainer
        position="top-right"
        autoClose={4000}
        hideProgressBar
        newestOnTop
        closeOnClick
        rtl={false}
        pauseOnFocusLoss
        draggable
        pauseOnHover
        closeButton={false}
        icon={false}
        className="w-96"
      />
    </>
  );
};
