FROM --platform=linux/amd64 ubuntu:20.04

# 更新系統並安裝必需的軟件包
RUN apt-get update && \
    apt-get install -y wget

# 下載 XAMPP
RUN wget https://sourceforge.net/projects/xampp/files/XAMPP%20Linux/8.2.0/xampp-linux-x64-8.2.0-0-installer.run -O xampp-installer.run

# 安裝 XAMPP
RUN chmod +x xampp-installer.run && \
    ./xampp-installer.run --mode unattended && \
    rm xampp-installer.run

# 開放 XAMPP 的端口
EXPOSE 80 443 3306

# 啟動 XAMPP
CMD /opt/lampp/lampp start && tail -f /opt/lampp/logs/error_log