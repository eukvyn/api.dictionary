# API Dictionary

Este projeto é uma API que consome e expande os recursos da Free Dictionary API, oferecendo funcionalidades adicionais, como caching e gerenciamento de histórico e favoritos.

## Tecnologias

- Laravel ^11.9
- PHP ^8.2
- MySQL 8.0
- Redis
- Docker

## Escolhas Técnicas e Decisões

### Laravel
A escolha do Laravel como o framework PHP foi baseada em sua robustez e vasto ecossistema. Laravel oferece uma estrutura bem definida que facilita o desenvolvimento de APIs seguras e escaláveis, além de um excelente suporte para autenticação, middleware e caching. A arquitetura MVC do Laravel ajudou a manter o código organizado e a implementação das camadas de serviço e controle se tornou simples e eficiente.

Além disso, a facilidade de integração com bibliotecas de terceiros (como a Free Dictionary API) fez do Laravel uma escolha natural, proporcionando uma curva de aprendizado rápida devido à sua documentação clara.

### Redis
Para otimizar o tempo de resposta da API, implementei o Redis para caching de dados. Ao armazenar as respostas da Free Dictionary API em cache, a API pode reduzir significativamente o número de requisições externas e melhorar o desempenho geral, especialmente em consultas repetidas. Redis foi escolhido por ser altamente performático e escalável, sendo uma solução ideal para armazenamento temporário de dados e cache de alto desempenho.

### Docker
O uso do Docker foi essencial para garantir um ambiente de desenvolvimento consistente e facilitar a configuração da infraestrutura. Com Docker, pude isolar todos os serviços necessários (PHP, MySQL, Redis) e garantir que o projeto funcione de maneira idêntica em qualquer máquina, independentemente do sistema operacional ou das configurações de software.

![docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white)

## Pré-requisitos

- Git
- Docker
- docker-compose

## Instalação

Siga os passos abaixo para configurar o projeto em seu ambiente local:

### 1. Clonar o Repositório

Clone este repositório para o seu ambiente local usando o seguinte comando:

```bash
git clone https://github.com/eukvyn/api.dictionary.git
cd api.dictionary
```

### 2. Suba os containers do projeto

Execute:

```bash
docker-compose up -d
```

### 3. Configurar o Ambiente

Copie o arquivo .env.example para um novo arquivo chamado .env:

```bash
cp .env.example .env
```

### 4. Acesse o container app

Execute:

```bash
docker-compose exec app bash
```

### 5. Instalar Dependências

Execute o Composer para instalar as dependências do projeto:

```bash
composer install
```

Em caso de falha da instalação de alguma lib, continuar a instalação ignorando a lib que falou. Exemplo: 

```bash
composer install --ignore-platform-req=ext-zip
```

### 6. Gerar Chave da Aplicação

Gere a chave da aplicação Laravel com o comando:

```bash
php artisan key:generate
```

### 7. Preparar o Banco de Dados

Cria o banco de dados que está especificado no arquivo .env e executa as migrations:

```bash
php artisan migrate
```

### 8. Executar Seeders

Popule o banco de dados com dados iniciais executando:

```bash
php artisan db:seed
```

Essa operação deve levar entre 2 - 4 minutos, pois nesse trecho é feito o download e inserção de cerca de 200 mil registros de palavras no banco de dados.

## Acesse o projeto

Com todas as configurações feitas,

Agora, a API estará rodando em http://localhost:8000.

>  This is a challenge by [Coodesh](https://coodesh.com/)
