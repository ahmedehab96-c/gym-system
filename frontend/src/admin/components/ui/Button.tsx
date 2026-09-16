import type { ButtonHTMLAttributes, ReactNode } from "react";
import { motion } from "framer-motion";
import { cn } from "../../../utils/cn";

type Variant = "primary" | "secondary" | "outline" | "ghost" | "danger";
type Size = "sm" | "md" | "lg" | "icon";

type NativeButtonProps = Omit<
  ButtonHTMLAttributes<HTMLButtonElement>,
  "children" | "onAnimationStart" | "onAnimationEnd" | "onAnimationIteration" | "onDrag" | "onDragStart" | "onDragEnd"
>;

interface ButtonProps extends NativeButtonProps {
  variant?: Variant;
  size?: Size;
  icon?: ReactNode;
  children?: ReactNode;
  className?: string;
}

const variantStyles: Record<Variant, string> = {
  primary:
    "bg-gradient-to-r from-a-accent to-a-accent-2 text-black shadow-[0_6px_20px_-6px_rgba(212,167,47,0.6)] hover:shadow-[0_10px_28px_-6px_rgba(212,167,47,0.75)]",
  secondary:
    "bg-a-surface-2 text-a-text border border-a-border hover:border-a-accent/50 dark:bg-a-dark-surface-2 dark:text-a-dark-text dark:border-a-dark-border",
  outline:
    "border border-a-border text-a-text hover:border-a-accent hover:text-a-accent-2 dark:border-a-dark-border dark:text-a-dark-text dark:hover:text-a-accent",
  ghost: "text-a-muted hover:text-a-text hover:bg-a-surface-2 dark:text-a-dark-muted dark:hover:text-a-dark-text dark:hover:bg-a-dark-surface-2",
  danger: "bg-rose-500/10 text-rose-500 border border-rose-500/30 hover:bg-rose-500/20",
};

const sizeStyles: Record<Size, string> = {
  sm: "px-3 py-1.5 text-xs gap-1.5",
  md: "px-4 py-2.5 text-sm gap-2",
  lg: "px-6 py-3 text-base gap-2",
  icon: "h-9 w-9 justify-center",
};

export function Button({ variant = "primary", size = "md", icon, children, className, ...rest }: ButtonProps) {
  return (
    <motion.button
      whileHover={{ scale: 1.02 }}
      whileTap={{ scale: 0.97 }}
      className={cn(
        "inline-flex cursor-pointer items-center rounded-xl font-medium transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50",
        variantStyles[variant],
        sizeStyles[size],
        className,
      )}
      {...rest}
    >
      {icon}
      {children}
    </motion.button>
  );
}
