import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { cn } from "../../utils/cn";

type Variant = "primary" | "outline" | "ghost";
type Size = "sm" | "md" | "lg";

interface BaseProps {
  variant?: Variant;
  size?: Size;
  icon?: ReactNode;
  className?: string;
  children: ReactNode;
}

interface ButtonAsButton extends BaseProps {
  to?: undefined;
  type?: "button" | "submit" | "reset";
  disabled?: boolean;
  onClick?: () => void;
  "aria-label"?: string;
}

interface ButtonAsLink extends BaseProps {
  to: string;
  onClick?: () => void;
}

type ButtonProps = ButtonAsButton | ButtonAsLink;

const variantStyles: Record<Variant, string> = {
  primary:
    "bg-gradient-to-r from-gold-300 via-gold-400 to-gold-500 text-ink shadow-[0_8px_30px_-8px_rgba(212,167,47,0.6)] hover:shadow-[0_12px_40px_-8px_rgba(212,167,47,0.8)]",
  outline: "border border-white/25 text-white hover:border-gold-300 hover:text-gold-200",
  ghost: "text-white/80 hover:text-gold-200",
};

const sizeStyles: Record<Size, string> = {
  sm: "px-4 py-2 text-xs",
  md: "px-6 py-3.5 text-sm",
  lg: "px-8 py-4.5 text-base",
};

export function Button(props: ButtonProps) {
  const { variant = "primary", size = "md", icon, className, children } = props;

  const classes = cn(
    "group relative inline-flex items-center justify-center gap-2 rounded-full font-semibold uppercase tracking-wider transition-all duration-300 cursor-pointer",
    variantStyles[variant],
    sizeStyles[size],
    className,
  );

  const content = (
    <>
      <span>{children}</span>
      {icon && (
        <span className="transition-transform duration-300 group-hover:translate-x-1">{icon}</span>
      )}
    </>
  );

  const motionProps = {
    whileHover: { scale: 1.03 },
    whileTap: { scale: 0.97 },
  };

  if ("to" in props && props.to) {
    return (
      <motion.div {...motionProps} className="inline-block">
        <Link to={props.to} className={classes} onClick={props.onClick}>
          {content}
        </Link>
      </motion.div>
    );
  }

  const { type, disabled, onClick, "aria-label": ariaLabel } = props as ButtonAsButton;

  return (
    <motion.button
      {...motionProps}
      className={classes}
      type={type ?? "button"}
      disabled={disabled}
      onClick={onClick}
      aria-label={ariaLabel}
    >
      {content}
    </motion.button>
  );
}
