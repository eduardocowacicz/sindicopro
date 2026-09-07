# syntax=docker/dockerfile:1.7

ARG NODE_VERSION=22

FROM node:${NODE_VERSION}-alpine AS node-base

WORKDIR /app

RUN apk add --no-cache su-exec \
    && mkdir -p /app/node_modules \
    && chown node:node /app/node_modules

COPY docker/entrypoint.sh /usr/local/bin/sindicopro-frontend
RUN chmod +x /usr/local/bin/sindicopro-frontend

ENTRYPOINT ["sindicopro-frontend"]

FROM node-base AS development

ENV NODE_ENV=development

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host=0.0.0.0", "--port=5173"]

FROM node-base AS build

ENV NODE_ENV=production

COPY package.json package-lock.json ./
RUN npm ci --include=dev

COPY . .

ARG VITE_API_URL=/
ENV VITE_API_URL=${VITE_API_URL}

RUN npm run build

FROM nginx:1.28-alpine AS production

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/ || exit 1

CMD ["nginx", "-g", "daemon off;"]
